<?php

namespace App\Services\Social;

use App\Models\SocialDesignType;
use App\Models\SocialTemplate;
use App\Models\SocialTemplateVersion;
use App\Models\User;
use App\Services\Logo\DesignDocument;
use App\Services\Logo\SvgRenderingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SocialTemplateService
{
    public function __construct(
        private readonly SocialTemplateComposer $composer,
        private readonly SvgRenderingService $renderer,
        private readonly SocialTemplateQualityService $quality,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $creator = null): SocialTemplate
    {
        return DB::transaction(function () use ($data, $creator) {
            $type = SocialDesignType::query()->with('platform')->findOrFail($data['design_type_id']);

            $design = $this->normalizeDesign(
                $data['design_json'] ?? $this->composer->compose($type, $data)
            );

            $svg = $data['svg'] ?? $this->composer->render($design);
            $report = $this->quality->inspect($design, $type);

            $template = SocialTemplate::create([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? null,
                'description' => $data['description'] ?? null,
                'platform_id' => $type->platform_id,
                'design_type_id' => $type->id,
                'industry_id' => $data['industry_id'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'width' => $type->width,
                'height' => $type->height,
                'aspect_ratio' => $type->aspect_ratio,
                'styles' => $data['styles'] ?? [],
                'keywords' => $data['keywords'] ?? [],
                'tags' => $data['tags'] ?? [],
                'palette' => $data['palette'] ?? ($design['meta']['palette'] ?? []),
                'fonts' => $data['fonts'] ?? [$design['meta']['font'] ?? 'Poppins'],
                'design_json' => $design,
                'svg' => $svg,
                'layer_config' => $data['layer_config'] ?? ['engine' => 'fabric', 'version' => 1],
                'asset_refs' => $data['asset_refs'] ?? [],
                'license_type' => $data['license_type'] ?? 'standard',
                'is_premium' => (bool) ($data['is_premium'] ?? false),
                'is_featured' => (bool) ($data['is_featured'] ?? false),
                'is_trending' => (bool) ($data['is_trending'] ?? false),
                'status' => $data['status'] ?? SocialTemplate::STATUS_DRAFT,
                'version' => 1,
                'source' => $data['source'] ?? 'manual',
                'fingerprint' => SocialTemplateFingerprint::for($design),
                'ai_meta' => $data['ai_meta'] ?? null,
                'quality_score' => $report['score'],
                'quality_report' => $report,
                'created_by' => $creator?->id,
                'published_at' => ($data['status'] ?? null) === SocialTemplate::STATUS_PUBLISHED ? now() : null,
            ]);

            $this->writeVersion($template, $design, $svg, 'Initial version', $creator, $report);

            return $template->fresh(['platform', 'designType', 'industry', 'category', 'creator']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SocialTemplate $template, array $data, ?User $editor = null): SocialTemplate
    {
        return DB::transaction(function () use ($template, $data, $editor) {
            $type = isset($data['design_type_id'])
                ? SocialDesignType::query()->with('platform')->findOrFail($data['design_type_id'])
                : $template->designType()->with('platform')->first();

            if ($type === null) {
                throw ValidationException::withMessages(['design_type_id' => 'A design type is required.']);
            }

            $designChanged = array_key_exists('design_json', $data) || (int) $template->design_type_id !== (int) $type->id;
            $design = $designChanged
                ? $this->normalizeDesign($data['design_json'] ?? $this->composer->compose($type, array_merge($template->toArray(), $data)))
                : $template->design_json;

            $svg = $designChanged ? $this->composer->render($design) : $template->svg;
            $report = $this->quality->inspect($design, $type);
            $version = $designChanged ? $template->version + 1 : $template->version;

            $template->fill([
                'name' => $data['name'] ?? $template->name,
                'slug' => $data['slug'] ?? $template->slug,
                'description' => $data['description'] ?? $template->description,
                'platform_id' => $type->platform_id,
                'design_type_id' => $type->id,
                'industry_id' => $data['industry_id'] ?? $template->industry_id,
                'category_id' => $data['category_id'] ?? $template->category_id,
                'width' => $type->width,
                'height' => $type->height,
                'aspect_ratio' => $type->aspect_ratio,
                'styles' => $data['styles'] ?? $template->styles,
                'keywords' => $data['keywords'] ?? $template->keywords,
                'tags' => $data['tags'] ?? $template->tags,
                'palette' => $data['palette'] ?? ($design['meta']['palette'] ?? $template->palette),
                'fonts' => $data['fonts'] ?? [$design['meta']['font'] ?? $template->fonts[0] ?? 'Poppins'],
                'design_json' => $design,
                'svg' => $svg,
                'layer_config' => $data['layer_config'] ?? $template->layer_config,
                'asset_refs' => $data['asset_refs'] ?? $template->asset_refs,
                'license_type' => $data['license_type'] ?? $template->license_type,
                'is_premium' => array_key_exists('is_premium', $data) ? (bool) $data['is_premium'] : $template->is_premium,
                'is_featured' => array_key_exists('is_featured', $data) ? (bool) $data['is_featured'] : $template->is_featured,
                'is_trending' => array_key_exists('is_trending', $data) ? (bool) $data['is_trending'] : $template->is_trending,
                'status' => $data['status'] ?? $template->status,
                'version' => $version,
                'fingerprint' => SocialTemplateFingerprint::for($design),
                'quality_score' => $report['score'],
                'quality_report' => $report,
                'published_at' => ($data['status'] ?? $template->status) === SocialTemplate::STATUS_PUBLISHED
                    ? ($template->published_at ?? now())
                    : $template->published_at,
            ]);

            $template->save();

            if ($designChanged) {
                $this->writeVersion($template, $design, $svg, $data['changelog'] ?? 'Design updated', $editor, $report);
            }

            return $template->fresh(['platform', 'designType', 'industry', 'category', 'creator']);
        });
    }

    public function publish(SocialTemplate $template): SocialTemplate
    {
        $report = $this->quality->inspect($template->design_json ?? [], $template->designType);

        if (! $report['valid']) {
            throw ValidationException::withMessages([
                'design_json' => $report['errors'],
            ]);
        }

        $template->update([
            'status' => SocialTemplate::STATUS_PUBLISHED,
            'published_at' => $template->published_at ?? now(),
            'quality_score' => $report['score'],
            'quality_report' => $report,
        ]);

        return $template->fresh();
    }

    public function unpublish(SocialTemplate $template): SocialTemplate
    {
        $template->update(['status' => SocialTemplate::STATUS_DRAFT, 'published_at' => null]);

        return $template->fresh();
    }

    public function archive(SocialTemplate $template): SocialTemplate
    {
        $template->update(['status' => SocialTemplate::STATUS_ARCHIVED]);

        return $template->fresh();
    }

    public function duplicate(SocialTemplate $template, ?User $creator = null): SocialTemplate
    {
        $copy = $template->replicate([
            'uuid', 'slug', 'published_at', 'usage_count', 'view_count', 'favorite_count',
        ]);

        $copy->name = $template->name.' Copy';
        $copy->status = SocialTemplate::STATUS_DRAFT;
        $copy->source = 'duplicate';
        $copy->version = 1;
        $copy->created_by = $creator?->id;
        $copy->save();

        $this->writeVersion(
            $copy,
            $copy->design_json,
            $copy->svg ?? $this->composer->render($copy->design_json),
            'Duplicated from '.$template->name,
            $creator,
            $copy->quality_report ?? $this->quality->inspect($copy->design_json ?? [], $copy->designType)
        );

        return $copy->fresh(['platform', 'designType', 'industry', 'category']);
    }

    /**
     * @param  array<int, int>  $ids
     * @return array{updated: int, action: string}
     */
    public function bulk(array $ids, string $action): array
    {
        $query = SocialTemplate::query()->whereIn('id', $ids);
        $updated = match ($action) {
            'publish' => $query->update([
                'status' => SocialTemplate::STATUS_PUBLISHED,
                'published_at' => now(),
                'updated_at' => now(),
            ]),
            'unpublish' => $query->update([
                'status' => SocialTemplate::STATUS_DRAFT,
                'published_at' => null,
                'updated_at' => now(),
            ]),
            'archive' => $query->update(['status' => SocialTemplate::STATUS_ARCHIVED, 'updated_at' => now()]),
            'delete' => $query->delete(),
            default => 0,
        };

        return ['updated' => (int) $updated, 'action' => $action];
    }

    /**
     * @return array<string, mixed>
     */
    public function export(SocialTemplate $template): array
    {
        return [
            'format' => 'logoforge.social-template.v1',
            'name' => $template->name,
            'description' => $template->description,
            'platform' => $template->platform?->slug,
            'design_type' => $template->designType?->slug,
            'industry' => $template->industry?->slug,
            'category' => $template->category?->slug,
            'styles' => $template->styles,
            'keywords' => $template->keywords,
            'tags' => $template->tags,
            'palette' => $template->palette,
            'fonts' => $template->fonts,
            'license_type' => $template->license_type,
            'is_premium' => $template->is_premium,
            'design_json' => $template->design_json,
            'layer_config' => $template->layer_config,
            'asset_refs' => $template->asset_refs,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function import(array $payload, ?User $creator = null): SocialTemplate
    {
        $designType = SocialDesignType::query()
            ->where('slug', (string) ($payload['design_type'] ?? ''))
            ->firstOrFail();

        return $this->create(array_merge($payload, [
            'design_type_id' => $designType->id,
            'name' => (string) ($payload['name'] ?? $designType->name.' template'),
            'source' => 'import',
            'status' => SocialTemplate::STATUS_DRAFT,
        ]), $creator);
    }

    /**
     * @param  array<string, mixed>  $design
     * @return array<string, mixed>
     */
    private function normalizeDesign(array $design): array
    {
        $document = (new DesignDocument($design))->toArray();
        $errors = (new DesignDocument($document))->validate();

        if ($errors !== []) {
            throw ValidationException::withMessages(['design_json' => $errors]);
        }

        return $document;
    }

    /**
     * @param  array<string, mixed>  $design
     * @param  array<string, mixed>  $report
     */
    private function writeVersion(
        SocialTemplate $template,
        array $design,
        string $svg,
        string $changelog,
        ?User $creator,
        array $report,
    ): SocialTemplateVersion {
        return SocialTemplateVersion::create([
            'template_id' => $template->id,
            'version' => $template->version ?? 1,
            'name' => $template->name,
            'design_json' => $design,
            'svg' => $svg,
            'changelog' => $changelog,
            'quality_report' => $report,
            'created_by' => $creator?->id,
        ]);
    }
}
