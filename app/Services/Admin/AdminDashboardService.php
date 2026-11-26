<?php

namespace App\Services\Admin;

use App\Models\AdminActivityLog;
use App\Models\AiGenerationLog;
use App\Models\ContentReport;
use App\Models\Download;
use App\Models\GeneratedLogo;
use App\Models\LogoAsset;
use App\Models\LogoTemplate;
use App\Models\SocialTemplate;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\UserDesign;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminDashboardService
{
    public function __construct(
        private readonly SystemHealthService $health,
        private readonly AiCostService $costs,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $from = now()->subDays(29)->startOfDay();
        $to = now();

        return [
            'stats' => $this->stats($from, $to),
            'charts' => $this->charts($from, $to),
            'recent' => $this->recent(),
            'health' => $this->health->snapshot(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function analytics(CarbonInterface $from, CarbonInterface $to): array
    {
        return [
            'range' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'stats' => $this->stats($from, $to),
            'charts' => $this->charts($from, $to),
            'tables' => [
                'top_templates' => $this->topTemplates(),
                'top_assets' => $this->topAssets(),
                'popular_searches' => $this->popularSearches($from, $to),
                'ai_costs' => $this->costs->summary($from, $to),
            ],
        ];
    }

    /**
     * @return array<string, int|float>
     */
    private function stats(CarbonInterface $from, CarbonInterface $to): array
    {
        if (! Schema::hasTable('social_templates') || ! Schema::hasTable('users')) {
            return $this->emptyStats();
        }

        try {
            return $this->computeStats($from, $to);
        } catch (\Throwable) {
            return $this->emptyStats();
        }
    }

    /**
     * @return array<string, int|float>
     */
    private function emptyStats(): array
    {
        return [
            'users_total' => 0,
            'users_today' => 0,
            'users_month' => 0,
            'users_active' => 0,
            'designs_total' => 0,
            'designs_range' => 0,
            'ai_generations' => 0,
            'ai_generations_range' => 0,
            'image_generations' => 0,
            'downloads' => 0,
            'templates_total' => 0,
            'templates_published' => 0,
            'templates_drafts' => 0,
            'assets_total' => 0,
            'storage_used' => 0,
            'ai_failed' => 0,
            'ai_pending' => 0,
            'queue_pending' => 0,
            'queue_failed' => 0,
            'open_tickets' => 0,
            'open_reports' => 0,
        ];
    }

    /**
     * @return array<string, int|float>
     */
    private function computeStats(CarbonInterface $from, CarbonInterface $to): array
    {
        $publishedSocial = SocialTemplate::query()->where('status', SocialTemplate::STATUS_PUBLISHED)->count();
        $hasLastActive = Schema::hasColumn('users', 'last_active_at');
        $activeUsers = $hasLastActive
            ? User::query()
                ->where(function ($query) {
                    $query->where('last_active_at', '>=', now()->subDays(30))
                        ->orWhereHas('designs', fn ($designs) => $designs->where('updated_at', '>=', now()->subDays(30)));
                })
                ->count()
            : User::query()->count();

        return [
            'users_total' => User::query()->count(),
            'users_today' => User::query()->whereDate('created_at', today())->count(),
            'users_month' => User::query()->where('created_at', '>=', now()->startOfMonth())->count(),
            'users_active' => $activeUsers,
            'designs_total' => UserDesign::query()->count(),
            'designs_range' => UserDesign::query()->whereBetween('created_at', [$from, $to])->count(),
            'ai_generations' => AiGenerationLog::query()->count(),
            'ai_generations_range' => AiGenerationLog::query()->whereBetween('created_at', [$from, $to])->count(),
            'image_generations' => AiGenerationLog::query()->where('purpose', 'like', '%image%')->count(),
            'downloads' => Download::query()->count(),
            'templates_total' => LogoTemplate::query()->count() + SocialTemplate::query()->count(),
            'templates_published' => LogoTemplate::query()->where('is_active', true)->count() + $publishedSocial,
            'templates_drafts' => SocialTemplate::query()->where('status', SocialTemplate::STATUS_DRAFT)->count(),
            'assets_total' => LogoAsset::query()->where('is_archived', false)->count(),
            'storage_used' => $this->health->snapshot()['storage']['used_bytes'] ?? 0,
            'ai_failed' => AiGenerationLog::query()->where('status', 'failed')->count(),
            'ai_pending' => AiGenerationLog::query()->whereIn('status', ['pending', 'running'])->count(),
            'queue_pending' => $this->queueCount('jobs'),
            'queue_failed' => $this->queueCount('failed_jobs'),
            'open_tickets' => SupportTicket::query()->whereIn('status', ['open', 'in_progress'])->count(),
            'open_reports' => ContentReport::query()->where('status', 'open')->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function charts(CarbonInterface $from, CarbonInterface $to): array
    {
        try {
            return $this->computeCharts($from, $to);
        } catch (\Throwable) {
            $empty = $this->series(collect(), $from, $to);

            return [
                'daily_users' => $empty,
                'daily_designs' => $empty,
                'daily_ai' => $empty,
                'designs_by_tool' => [],
                'designs_by_platform' => [],
                'ai_by_provider' => [],
                'ai_by_model' => [],
                'ai_costs' => ['total' => 0, 'requests' => 0, 'tokens_in' => 0, 'tokens_out' => 0, 'currency' => 'USD'],
            ];
        }
    }

    private function computeCharts(CarbonInterface $from, CarbonInterface $to): array
    {
        $users = User::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as day, count(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $designs = UserDesign::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as day, count(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $ai = AiGenerationLog::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as day, count(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return [
            'daily_users' => $this->series($users, $from, $to),
            'daily_designs' => $this->series($designs, $from, $to),
            'daily_ai' => $this->series($ai, $from, $to),
            'designs_by_tool' => UserDesign::query()
                ->selectRaw("COALESCE(format, 'logo') as tool, count(*) as total")
                ->groupBy('tool')
                ->orderByDesc('total')
                ->limit(12)
                ->get(),
            'designs_by_platform' => DB::table('social_templates')
                ->join('social_platforms', 'social_platforms.id', '=', 'social_templates.platform_id')
                ->select('social_platforms.name', 'social_platforms.slug', DB::raw('count(*) as total'))
                ->groupBy('social_platforms.id', 'social_platforms.name', 'social_platforms.slug')
                ->orderByDesc('total')
                ->get(),
            'ai_by_provider' => AiGenerationLog::query()
                ->selectRaw("COALESCE(provider, 'unknown') as provider, count(*) as total")
                ->groupBy('provider')
                ->orderByDesc('total')
                ->get(),
            'ai_by_model' => AiGenerationLog::query()
                ->selectRaw("COALESCE(model, 'unknown') as model, count(*) as total")
                ->groupBy('model')
                ->orderByDesc('total')
                ->limit(12)
                ->get(),
            'ai_costs' => $this->costs->summary($from, $to),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function recent(): array
    {
        try {
            return $this->computeRecent();
        } catch (\Throwable) {
            return [
                'users' => [],
                'designs' => [],
                'assets' => [],
                'templates' => [],
                'ai_generations' => [],
                'failed_jobs' => [],
                'activity' => [],
                'reports' => [],
            ];
        }
    }

    private function computeRecent(): array
    {
        $userColumns = ['id', 'name', 'email', 'is_admin', 'created_at'];
        if (Schema::hasColumn('users', 'status')) {
            $userColumns[] = 'status';
        }

        return [
            'users' => User::query()->latest()->limit(8)->get($userColumns),
            'designs' => UserDesign::query()->with('user:id,name,email')->latest()->limit(8)->get(['id', 'uuid', 'name', 'business_name', 'format', 'user_id', 'created_at']),
            'assets' => LogoAsset::query()->latest()->limit(8)->get(['id', 'name', 'asset_type', 'category', 'image_url', 'created_at']),
            'templates' => SocialTemplate::query()->with('platform:id,name')->latest()->limit(8)->get(['id', 'name', 'slug', 'status', 'platform_id', 'created_at']),
            'ai_generations' => AiGenerationLog::query()->latest()->limit(8)->get(['id', 'provider', 'model', 'purpose', 'status', 'tokens_in', 'tokens_out', 'estimated_cost', 'created_at']),
            'failed_jobs' => $this->failedJobs(),
            'activity' => AdminActivityLog::query()->with('user:id,name')->latest()->limit(10)->get(),
            'reports' => ContentReport::query()->with('reporter:id,name')->latest()->limit(8)->get(),
        ];
    }

    /**
     * @return array<int, array{date: string, total: int}>
     */
    private function series($rows, CarbonInterface $from, CarbonInterface $to): array
    {
        $indexed = collect($rows)->keyBy(fn ($row) => (string) $row->day);
        $result = [];
        $cursor = $from->copy()->startOfDay();

        while ($cursor->lte($to)) {
            $key = $cursor->toDateString();
            $result[] = ['date' => $key, 'total' => (int) ($indexed[$key]->total ?? 0)];
            $cursor->addDay();
        }

        return $result;
    }

    private function queueCount(string $table): int
    {
        return Schema::hasTable($table) ? (int) DB::table($table)->count() : 0;
    }

    /**
     * @return array<int, mixed>
     */
    private function failedJobs(): array
    {
        if (! Schema::hasTable('failed_jobs')) {
            return [];
        }

        return DB::table('failed_jobs')
            ->latest('failed_at')
            ->limit(8)
            ->get(['id', 'uuid', 'connection', 'queue', 'payload', 'exception', 'failed_at'])
            ->map(fn ($job) => [
                'id' => $job->id,
                'uuid' => $job->uuid,
                'connection' => $job->connection,
                'queue' => $job->queue,
                'exception' => mb_substr((string) $job->exception, 0, 300),
                'failed_at' => $job->failed_at,
            ])
            ->all();
    }

    /**
     * @return array<int, mixed>
     */
    private function topTemplates(): array
    {
        $logo = LogoTemplate::query()
            ->orderByDesc('popularity')
            ->orderByDesc('downloads_count')
            ->limit(10)
            ->get(['id', 'name', 'slug', 'layout', 'popularity', 'downloads_count'])
            ->map(fn ($template) => [
                'type' => 'logo',
                'name' => $template->name,
                'slug' => $template->slug,
                'usage' => $template->popularity,
                'downloads' => $template->downloads_count,
            ]);

        $social = SocialTemplate::query()
            ->orderByDesc('usage_count')
            ->limit(10)
            ->get(['id', 'name', 'slug', 'usage_count', 'view_count'])
            ->map(fn ($template) => [
                'type' => 'social',
                'name' => $template->name,
                'slug' => $template->slug,
                'usage' => $template->usage_count,
                'downloads' => $template->view_count,
            ]);

        return $logo->concat($social)->sortByDesc('usage')->take(12)->values()->all();
    }

    /**
     * @return array<int, mixed>
     */
    private function topAssets(): array
    {
        return GeneratedLogo::query()
            ->whereNotNull('symbol_asset_id')
            ->selectRaw('symbol_asset_id, count(*) as total')
            ->with('symbolAsset:id,name,slug,image_url,asset_type')
            ->groupBy('symbol_asset_id')
            ->orderByDesc('total')
            ->limit(12)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->symbolAsset?->id,
                'name' => $row->symbolAsset?->name ?? 'Deleted asset',
                'slug' => $row->symbolAsset?->slug,
                'image_url' => $row->symbolAsset?->image_url,
                'asset_type' => $row->symbolAsset?->asset_type,
                'usage' => (int) $row->total,
            ])
            ->all();
    }

    /**
     * @return array<int, mixed>
     */
    private function popularSearches(CarbonInterface $from, CarbonInterface $to): array
    {
        if (! Schema::hasTable('social_template_events')) {
            return [];
        }

        return DB::table('social_template_events')
            ->where('event', 'search')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(meta, '$.query')) as query, count(*) as total")
            ->groupBy('query')
            ->orderByDesc('total')
            ->limit(12)
            ->get()
            ->all();
    }
}
