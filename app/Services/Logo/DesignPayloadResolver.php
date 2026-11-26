<?php

namespace App\Services\Logo;

use App\Models\GeneratedLogo;
use App\Models\LogoTemplate;
use App\Models\UserDesign;
use RuntimeException;

/**
 * Rebuilds an authoritative design document from a compact client payload.
 *
 * The editor never uploads raw vector geometry for previews. It sends a source
 * reference (generated logo, template or saved design) plus text overrides and
 * a colour diff, and the server resolves the stored artwork itself.
 */
class DesignPayloadResolver
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function resolve(array $payload): array
    {
        $design = $this->baseDesign($payload);

        if (isset($payload['text']) && is_array($payload['text'])) {
            $design = $this->applyText($design, $payload['text']);
        }

        if (isset($payload['color_updates']) && is_array($payload['color_updates'])) {
            $design = $this->applyColors($design, $payload['color_updates']);
        }

        if (isset($payload['font_family']) && is_string($payload['font_family'])) {
            $design = $this->applyFont($design, $payload['font_family']);
        }

        return $design;
    }

    private function baseDesign(array $payload): array
    {
        $source = $payload['source'] ?? null;

        if (isset($payload['design_json']) && is_array($payload['design_json'])) {
            return $payload['design_json'];
        }

        if (is_array($source)) {
            $type = $source['type'] ?? null;
            $id = $source['id'] ?? null;

            if ($type === 'generated' && $id !== null) {
                $logo = GeneratedLogo::query()->find($id);

                if ($logo !== null) {
                    return $logo->design_json;
                }
            }

            if ($type === 'design' && is_string($id)) {
                $design = UserDesign::query()->where('uuid', $id)->first();

                if ($design !== null) {
                    return $design->design_json;
                }
            }

            if ($type === 'template' && is_string($id)) {
                $template = LogoTemplate::query()
                    ->where('template_token', $id)
                    ->orWhere('slug', $id)
                    ->first();

                if ($template !== null) {
                    return $template->design_json;
                }
            }
        }

        throw new RuntimeException('No design source was provided.');
    }

    /**
     * @param  array<string, mixed>  $text
     */
    private function applyText(array $design, array $text): array
    {
        foreach ($design['objects'] as $index => $object) {
            if (($object['type'] ?? null) !== 'i-text') {
                continue;
            }

            $role = $object['role'] ?? null;
            $value = match ($role) {
                'primary-text' => $text['primary'] ?? null,
                'new-text' => $text['tagline'] ?? null,
                default => null,
            };

            if (is_string($value)) {
                $design['objects'][$index]['text'] = $value;
            }
        }

        return $design;
    }

    /**
     * @param  array<int, array{from_color?: string, to_color?: string}>  $updates
     */
    private function applyColors(array $design, array $updates): array
    {
        $map = [];

        foreach ($updates as $update) {
            $from = $update['from_color'] ?? null;
            $to = $update['to_color'] ?? null;

            if (is_string($from) && is_string($to)) {
                $map[mb_strtolower($from)] = $to;
            }
        }

        if ($map === []) {
            return $design;
        }

        $recolor = function (array $objects) use (&$recolor, $map): array {
            return array_map(function (array $object) use (&$recolor, $map) {
                foreach (['fill', 'stroke'] as $property) {
                    if (isset($object[$property]) && is_string($object[$property])) {
                        $key = mb_strtolower($object[$property]);

                        if (isset($map[$key])) {
                            $object[$property] = $map[$key];
                        }
                    } elseif (isset($object[$property]) && is_array($object[$property]) && isset($object[$property]['stops'])) {
                        foreach ($object[$property]['stops'] as $stopIndex => $stop) {
                            $stopColor = mb_strtolower((string) ($stop['color'] ?? ''));
                            if ($stopColor !== '' && isset($map[$stopColor])) {
                                $object[$property]['stops'][$stopIndex]['color'] = $map[$stopColor];
                            }
                        }
                    }
                }

                if (! empty($object['objects'])) {
                    $object['objects'] = $recolor($object['objects']);
                }

                return $object;
            }, $objects);
        };

        $design['objects'] = $recolor($design['objects']);

        return $design;
    }

    private function applyFont(array $design, string $family): array
    {
        foreach ($design['objects'] as $index => $object) {
            if (($object['type'] ?? null) === 'i-text') {
                $design['objects'][$index]['fontFamily'] = $family;
            }
        }

        return $design;
    }
}
