<?php

namespace App\Services\Logo;

/**
 * The canonical, Fabric.js compatible design document.
 *
 * Everything the platform stores or exports is expressed with this schema, so
 * a saved design can be reopened in the editor, rendered to SVG on the server,
 * and exported to PNG/PDF without ever flattening to a raster image.
 */
class DesignDocument
{
    public const CANVAS_WIDTH = 650;

    public const CANVAS_HEIGHT = 520;

    public function __construct(public array $data)
    {
    }

    public static function make(array $objects, array $canvas = [], array $meta = []): self
    {
        return new self([
            'version' => 1,
            'canvas' => array_merge([
                'width' => self::CANVAS_WIDTH,
                'height' => self::CANVAS_HEIGHT,
                'background' => 'transparent',
            ], $canvas),
            'objects' => $objects,
            'meta' => $meta,
        ]);
    }

    public function objects(): array
    {
        return $this->data['objects'] ?? [];
    }

    public function canvas(): array
    {
        return $this->data['canvas'] ?? ['width' => self::CANVAS_WIDTH, 'height' => self::CANVAS_HEIGHT];
    }

    public function meta(): array
    {
        return $this->data['meta'] ?? [];
    }

    public function find(string $id): ?array
    {
        foreach ($this->objects() as $object) {
            if (($object['id'] ?? null) === $id) {
                return $object;
            }
        }

        return null;
    }

    public function withObjects(array $objects): self
    {
        $this->data['objects'] = $objects;

        return $this;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Structural validation used when accepting design payloads from clients or
     * imports. Returns a list of human readable problems (empty when valid).
     *
     * @return array<int, string>
     */
    public function validate(): array
    {
        $errors = [];

        foreach ($this->objects() as $index => $object) {
            $type = $object['type'] ?? null;

            if (! in_array($type, ['rect', 'circle', 'ellipse', 'polygon', 'line', 'path', 'group', 'i-text', 'textbox', 'image'], true)) {
                $errors[] = "Object {$index} has an unsupported type.";

                continue;
            }

            if (in_array($type, ['rect', 'circle', 'ellipse', 'polygon', 'line', 'path', 'image'], true) && ! isset($object['id'], $object['role'])) {
                $errors[] = "Object {$index} is missing an id or role.";
            }

            if ($type === 'i-text' && ! isset($object['text'], $object['fontFamily'], $object['fontSize'])) {
                $errors[] = "Text object {$index} is missing text, fontFamily or fontSize.";
            }

            if ($type === 'group' && ! is_array($object['objects'] ?? null)) {
                $errors[] = "Group {$index} must contain an objects array.";
            }
        }

        return $errors;
    }
}
