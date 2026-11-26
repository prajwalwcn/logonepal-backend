<?php

namespace App\Services\Admin;

use App\Models\AiProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SystemHealthService
{
    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        $checks = [];
        $started = microtime(true);

        try {
            DB::select('select 1');
            $checks[] = $this->check('database', 'Database', 'pass', 'Connection established in '.round((microtime(true) - $started) * 1000).' ms.');
        } catch (Throwable $error) {
            $checks[] = $this->check('database', 'Database', 'fail', $error->getMessage());
        }

        try {
            $probe = 'health-probe-'.now()->timestamp.'.txt';
            Storage::disk('public')->put($probe, 'ok');
            $writable = Storage::disk('public')->exists($probe);
            Storage::disk('public')->delete($probe);
            $checks[] = $this->check(
                'storage',
                'Public storage',
                $writable ? 'pass' : 'fail',
                $writable ? 'Public disk is writable.' : 'Public disk is not writable.',
            );
        } catch (Throwable $error) {
            $checks[] = $this->check('storage', 'Public storage', 'fail', $error->getMessage());
        }

        $queueDriver = (string) config('queue.default');
        $checks[] = $this->check(
            'queue',
            'Queue driver',
            $queueDriver === 'database' ? 'pass' : 'warn',
            $queueDriver === 'database' ? 'Database queue is configured.' : "Queue driver is {$queueDriver}; database queue is recommended.",
        );

        $pending = $this->tableCount('jobs');
        $failed = $this->tableCount('failed_jobs');
        $checks[] = $this->check(
            'failed_jobs',
            'Failed jobs',
            $failed === 0 ? 'pass' : 'warn',
            $failed === 0 ? 'No failed jobs.' : "{$failed} failed jobs require review.",
        );

        $activeProviders = AiProvider::query()->where('is_active', true)->count();
        $checks[] = $this->check(
            'ai_provider',
            'AI provider',
            $activeProviders > 0 ? 'pass' : 'warn',
            $activeProviders > 0 ? "{$activeProviders} active AI provider(s)." : 'No active AI provider is configured; deterministic fallbacks will be used.',
        );

        $checks[] = $this->check(
            'debug',
            'Application mode',
            app()->environment('production') && config('app.debug') ? 'warn' : 'pass',
            app()->environment('production') && config('app.debug') ? 'Debug mode is enabled in production.' : 'Application mode is '.app()->environment().'.',
        );

        return [
            'status' => collect($checks)->where('status', 'fail')->isEmpty() ? 'healthy' : 'degraded',
            'checks' => $checks,
            'queue' => [
                'driver' => $queueDriver,
                'pending' => $pending,
                'failed' => $failed,
                'created_last_hour' => $this->tableCount('jobs', fn ($query) => $query->where('created_at', '>=', now()->subHour())),
            ],
            'storage' => [
                'disk' => 'public',
                'used_bytes' => $this->storageBytes(),
                'free_bytes' => @disk_free_space(storage_path()) ?: null,
            ],
            'runtime' => [
                'php' => PHP_VERSION,
                'laravel' => app()->version(),
                'environment' => app()->environment(),
                'timezone' => config('app.timezone'),
            ],
        ];
    }

    /**
     * @return array{id: string, label: string, status: string, detail: string}
     */
    private function check(string $id, string $label, string $status, string $detail): array
    {
        return compact('id', 'label', 'status', 'detail');
    }

    private function tableCount(string $table, ?callable $scope = null): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        $query = DB::table($table);
        if ($scope !== null) {
            $scope($query);
        }

        return (int) $query->count();
    }

    private function storageBytes(): int
    {
        try {
            return (int) collect(Storage::disk('public')->allFiles())
                ->sum(fn (string $file) => Storage::disk('public')->size($file));
        } catch (Throwable) {
            return 0;
        }
    }
}
