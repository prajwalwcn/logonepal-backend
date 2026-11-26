<?php

namespace App\Services\Social;

use App\Models\SocialTemplate;
use App\Models\SocialTemplateEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SocialTemplateAnalyticsService
{
    public function record(SocialTemplate $template, string $event, ?User $user = null, array $meta = []): SocialTemplateEvent
    {
        $record = SocialTemplateEvent::create([
            'template_id' => $template->id,
            'user_id' => $user?->id,
            'event' => $event,
            'ip_address' => request()->ip(),
            'meta' => $meta === [] ? null : $meta,
            'created_at' => now(),
        ]);

        match ($event) {
            'view' => $template->increment('view_count'),
            'favorite' => $template->increment('favorite_count'),
            'use', 'download', 'resize' => $template->increment('usage_count'),
            default => null,
        };

        return $record;
    }

    public function recordSearch(string $query, ?User $user = null): void
    {
        $query = trim($query);

        if ($query === '') {
            return;
        }

        SocialTemplateEvent::create([
            'template_id' => null,
            'user_id' => $user?->id,
            'event' => 'search',
            'ip_address' => request()->ip(),
            'meta' => ['query' => mb_substr($query, 0, 120)],
            'created_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $base = SocialTemplate::query();

        return [
            'totals' => [
                'templates' => (clone $base)->count(),
                'published' => (clone $base)->where('status', SocialTemplate::STATUS_PUBLISHED)->count(),
                'drafts' => (clone $base)->where('status', SocialTemplate::STATUS_DRAFT)->count(),
                'archived' => (clone $base)->where('status', SocialTemplate::STATUS_ARCHIVED)->count(),
                'ai_generated' => (clone $base)->whereIn('source', ['ai', 'variation'])->count(),
                'usage' => (int) (clone $base)->sum('usage_count'),
                'views' => (int) (clone $base)->sum('view_count'),
                'favorites' => (int) (clone $base)->sum('favorite_count'),
            ],
            'by_platform' => DB::table('social_templates')
                ->join('social_platforms', 'social_platforms.id', '=', 'social_templates.platform_id')
                ->select('social_platforms.name', 'social_platforms.slug', DB::raw('count(*) as total'), DB::raw('sum(social_templates.usage_count) as `usage`'))
                ->groupBy('social_platforms.id', 'social_platforms.name', 'social_platforms.slug')
                ->orderByDesc('total')
                ->get(),
            'by_industry' => DB::table('social_templates')
                ->join('industries', 'industries.id', '=', 'social_templates.industry_id')
                ->select('industries.name', 'industries.slug', DB::raw('count(*) as total'))
                ->groupBy('industries.id', 'industries.name', 'industries.slug')
                ->orderByDesc('total')
                ->limit(10)
                ->get(),
            'top_templates' => SocialTemplate::query()
                ->with(['platform:id,name,slug', 'designType:id,name,slug'])
                ->orderByDesc('usage_count')
                ->orderByDesc('view_count')
                ->limit(10)
                ->get(['id', 'name', 'slug', 'platform_id', 'design_type_id', 'usage_count', 'view_count', 'favorite_count']),
            'events_last_30_days' => SocialTemplateEvent::query()
                ->where('created_at', '>=', now()->subDays(30))
                ->select('event', DB::raw('count(*) as total'))
                ->groupBy('event')
                ->get(),
            'popular_searches' => SocialTemplateEvent::query()
                ->where('event', 'search')
                ->where('created_at', '>=', now()->subDays(30))
                ->select(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(meta, '$.query')) as query"), DB::raw('count(*) as total'))
                ->groupBy('query')
                ->orderByDesc('total')
                ->limit(10)
                ->get(),
        ];
    }
}
