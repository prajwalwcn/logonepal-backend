<?php

namespace App\Services\Social;

use App\Models\ColorPalette;
use App\Models\DesignPreset;
use App\Models\LayoutPreset;
use App\Models\SocialDesignType;
use App\Models\SocialTemplate;
use App\Models\SocialTemplateVersion;
use App\Models\TypographyPreset;
use App\Models\User;
use App\Support\Color;
use Illuminate\Support\Facades\DB;

/**
 * Rule-based, deterministic template beautification engine (no AI).
 *
 * Improves spacing, alignment, typography hierarchy, WCAG contrast, safe-area
 * compliance, and decoration balance while preserving all editable Fabric.js objects
 * and storing every change as a new SocialTemplateVersion.
 */
class TemplateBeautifierService
{
    public function __construct(
        private readonly SocialTemplateComposer $composer,
        private readonly SocialTemplateQualityService $quality,
    ) {
    }

    /**
     * Preview rule-based improvements without persisting to the database.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function preview(SocialTemplate $template, array $options = []): array
    {
        $template->loadMissing(['designType.platform']);
        $type = $template->designType;

        $originalDesign = $template->design_json ?? [];
        $beforeReport = $this->quality->inspect($originalDesign, $type);

        $result = $this->transform($originalDesign, $type, $options);
        $afterSvg = $this->composer->render($result['design']);
        $afterReport = $this->quality->inspect($result['design'], $type);

        return [
            'before' => [
                'svg' => $template->svg,
                'quality_score' => $beforeReport['score'],
                'quality_report' => $beforeReport,
            ],
            'after' => [
                'design_json' => $result['design'],
                'svg' => $afterSvg,
                'quality_score' => $afterReport['score'],
                'quality_report' => $afterReport,
            ],
            'changes' => $result['changes'],
        ];
    }

    /**
     * Apply rule-based improvements and record a new template version.
     *
     * @param  array<string, mixed>  $options
     */
    public function apply(SocialTemplate $template, array $options = [], ?User $editor = null): SocialTemplate
    {
        return DB::transaction(function () use ($template, $options, $editor) {
            $template->loadMissing(['designType.platform']);
            $type = $template->designType;

            $result = $this->transform($template->design_json ?? [], $type, $options);
            $design = $result['design'];
            $svg = $this->composer->render($design);
            $report = $this->quality->inspect($design, $type);
            $nextVersion = ($template->version ?? 1) + 1;

            $template->update([
                'design_json' => $design,
                'svg' => $svg,
                'palette' => $design['meta']['palette'] ?? $template->palette,
                'fonts' => array_values(array_unique(array_filter([
                    $design['meta']['heading_font'] ?? null,
                    $design['meta']['body_font'] ?? null,
                ]))) ?: $template->fonts,
                'version' => $nextVersion,
                'fingerprint' => SocialTemplateFingerprint::for($design),
                'quality_score' => $report['score'],
                'quality_report' => $report,
            ]);

            $summary = $result['changes'] !== []
                ? 'Beautified: '.implode('; ', array_slice($result['changes'], 0, 4))
                : 'Rule-based beautification pass';

            SocialTemplateVersion::create([
                'template_id' => $template->id,
                'version' => $nextVersion,
                'name' => $template->name,
                'design_json' => $design,
                'svg' => $svg,
                'changelog' => mb_substr($summary, 0, 240),
                'quality_report' => $report,
                'created_by' => $editor?->id,
            ]);

            return $template->fresh(['platform', 'designType', 'industry', 'category', 'versions']);
        });
    }

    /**
     * Restore a previous template version as a brand-new version (non-destructive).
     */
    public function restoreVersion(SocialTemplate $template, SocialTemplateVersion $version, ?User $editor = null): SocialTemplate
    {
        return DB::transaction(function () use ($template, $version, $editor) {
            $template->loadMissing(['designType']);
            $design = $version->design_json ?? $template->design_json;
            $svg = $version->svg ?: $this->composer->render($design);
            $report = $this->quality->inspect($design, $template->designType);
            $nextVersion = ($template->version ?? 1) + 1;

            $template->update([
                'design_json' => $design,
                'svg' => $svg,
                'version' => $nextVersion,
                'fingerprint' => SocialTemplateFingerprint::for($design),
                'quality_score' => $report['score'],
                'quality_report' => $report,
            ]);

            SocialTemplateVersion::create([
                'template_id' => $template->id,
                'version' => $nextVersion,
                'name' => $template->name,
                'design_json' => $design,
                'svg' => $svg,
                'changelog' => "Restored from v{$version->version}",
                'quality_report' => $report,
                'created_by' => $editor?->id,
            ]);

            return $template->fresh(['platform', 'designType', 'industry', 'category', 'versions']);
        });
    }

    /**
     * @param  array<string, mixed>  $design
     * @param  array<string, mixed>  $options
     * @return array{design: array<string, mixed>, changes: array<int, string>}
     */
    public function transform(array $design, ?SocialDesignType $type = null, array $options = []): array
    {
        $changes = [];
        $canvas = $design['canvas'] ?? ['width' => 1080, 'height' => 1080];
        $width = max(100.0, (float) ($canvas['width'] ?? ($type?->width ?? 1080)));
        $height = max(100.0, (float) ($canvas['height'] ?? ($type?->height ?? 1080)));

        $designPreset = ! empty($options['design_preset_id'])
            ? DesignPreset::with(['colorPalette', 'typographyPreset', 'layoutPreset'])->find($options['design_preset_id'])
            : null;
        $palette = ! empty($options['color_palette_id'])
            ? ColorPalette::find($options['color_palette_id'])
            : $designPreset?->colorPalette;
        $typography = ! empty($options['typography_preset_id'])
            ? TypographyPreset::find($options['typography_preset_id'])
            : $designPreset?->typographyPreset;
        $layout = ! empty($options['layout_preset_id'])
            ? LayoutPreset::find($options['layout_preset_id'])
            : $designPreset?->layoutPreset;

        $fixContrast = (bool) ($options['fix_contrast'] ?? true);
        $fitOverflow = (bool) ($options['fit_overflow'] ?? true);
        $enforceSafeArea = (bool) ($options['enforce_safe_area'] ?? true);
        $pruneDecorations = (bool) ($options['prune_decorations'] ?? ($designPreset?->decoration_rules['prune_excess'] ?? false));

        $safe = $this->safeMargins($type, $layout, $width, $height);
        $safeWidth = max(120.0, $width - $safe['left'] - $safe['right']);

        $objects = is_array($design['objects'] ?? null) ? $design['objects'] : [];

        // 1. Resolve palette colors
        $bgColor = $this->currentBgColor($objects);
        $headingColor = null;
        $bodyColor = null;
        $accentColor = null;

        if ($palette !== null) {
            $colors = is_array($palette->colors) ? $palette->colors : [];
            $bgColor = $palette->background_color ?: ($palette->is_dark ? ($colors[0] ?? '#0F172A') : '#FFFFFF');
            $headingColor = $palette->heading_color ?: ($palette->is_dark ? '#FFFFFF' : ($colors[0] ?? '#0F172A'));
            $bodyColor = $palette->body_color ?: $headingColor;
            $accentColor = $palette->accent_color ?: ($colors[1] ?? $headingColor);
            $design['meta']['palette'] = $colors;
            $changes[] = "Applied color palette \"{$palette->name}\"";
        }

        // 2. Prune excessive decorative layers when requested
        if ($pruneDecorations) {
            $maxDecor = (int) ($designPreset?->decoration_rules['max_decorations'] ?? 4);
            $decorSeen = 0;
            $pruned = 0;
            $filtered = [];
            foreach ($objects as $obj) {
                if (! is_array($obj)) {
                    continue;
                }
                $role = (string) ($obj['role'] ?? '');
                if (in_array($role, ['shape', 'shape-ribbon', 'shape-frame', 'decorative'], true)) {
                    $decorSeen++;
                    if ($decorSeen > $maxDecor) {
                        $pruned++;
                        continue;
                    }
                }
                $filtered[] = $obj;
            }
            if ($pruned > 0) {
                $objects = $filtered;
                $changes[] = "Pruned {$pruned} excess decorative layers";
            }
        }

        // 3. Apply palette, typography, alignment, and safe-area clamping per object
        $textIndices = [];
        foreach ($objects as $idx => &$obj) {
            if (! is_array($obj)) {
                continue;
            }

            $role = (string) ($obj['role'] ?? '');
            $objType = (string) ($obj['type'] ?? '');

            if ($role === 'background' && $palette !== null) {
                $obj['fill'] = ['type' => 'solid', 'color' => $bgColor];
                continue;
            }

            if ($palette !== null && in_array($role, ['shape', 'shape-ribbon', 'shape-frame', 'decorative', 'cta-bg'], true) && $accentColor !== null) {
                $obj['fill'] = ['type' => 'solid', 'color' => $accentColor];
            }

            if ($objType === 'i-text') {
                $textIndices[] = $idx;
                $isHeading = in_array($role, ['primary-text', 'headline', 'brand'], true);

                if ($typography !== null) {
                    $obj['fontFamily'] = $isHeading ? $typography->heading_font : $typography->body_font;
                    $obj['fontWeight'] = $isHeading ? $typography->heading_weight : $typography->body_weight;
                    $obj['lineHeight'] = $isHeading ? $typography->line_height_heading : $typography->line_height_body;
                    $obj['charSpacing'] = $isHeading ? $typography->letter_spacing_heading : $typography->letter_spacing_body;
                    if ($layout === null) {
                        $obj['textAlign'] = $typography->text_alignment;
                    }
                }

                // Contrast validation & auto-correction
                if ($palette !== null || $fixContrast) {
                    $desired = $palette !== null
                        ? ($isHeading ? $headingColor : $bodyColor)
                        : ($this->extractSolidColor($obj['fill'] ?? null) ?? '#0F172A');

                    if ($desired !== null && $fixContrast && Color::contrast($desired, $bgColor) < 4.5) {
                        $desired = Color::contrast('#0F172A', $bgColor) >= Color::contrast('#FFFFFF', $bgColor)
                            ? '#0F172A'
                            : '#FFFFFF';
                        $changes[] = "Adjusted {$role} ink for WCAG AA contrast against {$bgColor}";
                    }
                    if ($desired !== null) {
                        $obj['fill'] = ['type' => 'solid', 'color' => $desired];
                    }
                }

                // Overflow fitting: shrink fontSize proportionally if estimated text width exceeds safe width
                if ($fitOverflow) {
                    $textStr = (string) ($obj['text'] ?? '');
                    $fontSize = max(12.0, (float) ($obj['fontSize'] ?? 36));
                    $maxW = $layout !== null && isset($layout->proportions['text_max_width'])
                        ? $width * (float) $layout->proportions['text_max_width']
                        : $safeWidth;

                    $approxCharWidth = $fontSize * 0.54;
                    $longestLine = max(array_map('mb_strlen', explode("\n", $textStr)) ?: [1]);
                    $estWidth = $longestLine * $approxCharWidth;

                    if ($estWidth > $maxW && $maxW > 0) {
                        $ratio = max(0.65, $maxW / $estWidth);
                        $obj['fontSize'] = round(max(14.0, $fontSize * $ratio), 1);
                        $changes[] = "Scaled {$role} font size to prevent horizontal overflow";
                    }
                }
            }

            // Layout alignment & vertical rhythm
            if ($layout !== null && $role !== 'background') {
                $props = $layout->proportions ?? [];
                $align = $layout->alignment ?? 'left';

                if ($objType === 'i-text') {
                    $obj['textAlign'] = $align;
                    $obj['left'] = match ($align) {
                        'center' => round($width / 2, 1),
                        'right' => round($width - $safe['right'], 1),
                        default => round($safe['left'], 1),
                    };
                    $obj['originX'] = $align;

                    if (in_array($role, ['primary-text', 'headline'], true) && isset($props['heading_y'])) {
                        $obj['top'] = round($height * (float) $props['heading_y'], 1);
                    } elseif (in_array($role, ['new-text', 'subhead', 'tagline'], true) && isset($props['subhead_y'])) {
                        $obj['top'] = round($height * (float) $props['subhead_y'], 1);
                    } elseif (in_array($role, ['cta', 'cta-text'], true) && isset($props['cta_y'])) {
                        $obj['top'] = round($height * (float) $props['cta_y'], 1);
                    }
                } elseif (in_array($role, ['logo', 'symbol'], true) && isset($props['logo_y'])) {
                    $obj['top'] = round($height * (float) $props['logo_y'], 1);
                }
            }

            // Enforce platform safe-area margins
            if ($enforceSafeArea && $role !== 'background') {
                $left = (float) ($obj['left'] ?? 0);
                $top = (float) ($obj['top'] ?? 0);
                if ($top < $safe['top']) {
                    $obj['top'] = round($safe['top'], 1);
                } elseif ($top > $height - $safe['bottom'] - 24) {
                    $obj['top'] = round($height - $safe['bottom'] - 36, 1);
                }
                if ($objType !== 'i-text' && $left < $safe['left']) {
                    $obj['left'] = round($safe['left'], 1);
                }
            }
        }
        unset($obj);

        // 4. Resolve vertical text overlaps deterministically (accounting for multi-line wrapping)
        if (count($textIndices) > 1) {
            usort($textIndices, fn ($a, $b) => ($objects[$a]['top'] ?? 0) <=> ($objects[$b]['top'] ?? 0));
            $minGap = max(16.0, $height * 0.028);
            for ($i = 1; $i < count($textIndices); $i++) {
                $prevIdx = $textIndices[$i - 1];
                $currIdx = $textIndices[$i];
                $prev = $objects[$prevIdx];
                $prevSize = max(16.0, (float) ($prev['fontSize'] ?? 24));
                $prevLh = max(1.05, (float) ($prev['lineHeight'] ?? 1.2));
                $prevText = (string) ($prev['text'] ?? '');
                $explicitLines = max(1, substr_count($prevText, "\n") + 1);
                $boxW = max(120.0, (float) ($prev['width'] ?? $safeWidth));
                $estLineChars = max(1.0, $boxW / ($prevSize * 0.54));
                $wrappedLines = (int) ceil(mb_strlen(str_replace("\n", '', $prevText)) / $estLineChars);
                $lines = max($explicitLines, $wrappedLines);
                $prevHeight = $lines * $prevSize * $prevLh;

                $prevTop = ($prev['originY'] ?? 'top') === 'center'
                    ? ($prev['top'] ?? 0) - ($prevHeight / 2)
                    : ($prev['top'] ?? 0);
                $prevBottom = $prevTop + $prevHeight;

                $curr = $objects[$currIdx];
                $currSize = max(14.0, (float) ($curr['fontSize'] ?? 20));
                $currTop = ($curr['originY'] ?? 'top') === 'center'
                    ? ($curr['top'] ?? 0) - ($currSize / 2)
                    : ($curr['top'] ?? 0);

                if ($currTop < $prevBottom + $minGap) {
                    $targetTop = $prevBottom + $minGap;
                    $newTop = ($curr['originY'] ?? 'top') === 'center'
                        ? $targetTop + ($currSize / 2)
                        : $targetTop;
                    $objects[$currIdx]['top'] = round(min($height - $safe['bottom'] - 24, $newTop), 1);
                }
            }
        }

        if ($typography !== null) {
            $design['meta']['heading_font'] = $typography->heading_font;
            $design['meta']['body_font'] = $typography->body_font;
            $design['meta']['font'] = $typography->heading_font;
            $changes[] = "Applied typography preset \"{$typography->name}\"";
        }
        if ($layout !== null) {
            $design['meta']['layout_preset'] = $layout->slug;
            $changes[] = "Applied layout preset \"{$layout->name}\"";
        }
        if ($designPreset !== null) {
            $design['meta']['design_preset'] = $designPreset->slug;
        }

        $design['objects'] = array_values($objects);

        return [
            'design' => $design,
            'changes' => array_values(array_unique($changes)),
        ];
    }

    /**
     * @return array{top: float, right: float, bottom: float, left: float}
     */
    private function safeMargins(?SocialDesignType $type, ?LayoutPreset $layout, float $width, float $height): array
    {
        $area = $layout?->safe_area ?? $type?->safe_area ?? [];

        return [
            'top' => $height * (float) ($area['top'] ?? 0.06),
            'right' => $width * (float) ($area['right'] ?? 0.06),
            'bottom' => $height * (float) ($area['bottom'] ?? 0.06),
            'left' => $width * (float) ($area['left'] ?? 0.06),
        ];
    }

    /**
     * @param  array<int, mixed>  $objects
     */
    private function currentBgColor(array $objects): string
    {
        foreach ($objects as $object) {
            if (is_array($object) && ($object['role'] ?? null) === 'background') {
                return $this->extractSolidColor($object['fill'] ?? null) ?? '#FFFFFF';
            }
        }

        return '#FFFFFF';
    }

    private function extractSolidColor(mixed $fill): ?string
    {
        if (is_string($fill) && str_starts_with($fill, '#')) {
            return $fill;
        }
        if (is_array($fill) && isset($fill['color']) && is_string($fill['color'])) {
            return $fill['color'];
        }

        return null;
    }
}
