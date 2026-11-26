<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminActivityLogger;
use App\Services\Admin\SystemHealthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminSystemController extends Controller
{
    public function __construct(
        private readonly SystemHealthService $health,
        private readonly AdminActivityLogger $activity,
    ) {
    }

    public function health(): JsonResponse
    {
        return response()->json(['data' => $this->health->snapshot()]);
    }

    public function queue(Request $request): JsonResponse
    {
        $status = $request->string('status')->toString();
        $pending = [];
        $failed = [];

        if (Schema::hasTable('jobs') && in_array($status, ['', 'all', 'pending', 'running'], true)) {
            $pending = DB::table('jobs')->latest('id')->limit(100)->get()->map(function ($job) {
                $payload = json_decode((string) $job->payload, true) ?: [];

                return [
                    'id' => $job->id,
                    'queue' => $job->queue,
                    'name' => $payload['displayName'] ?? 'Queued job',
                    'status' => $job->reserved_at ? 'running' : 'pending',
                    'attempts' => $job->attempts,
                    'created_at' => $job->created_at,
                ];
            })->all();
        }

        if (Schema::hasTable('failed_jobs') && in_array($status, ['', 'all', 'failed'], true)) {
            $failed = DB::table('failed_jobs')->latest('failed_at')->limit(100)->get()->map(function ($job) {
                $payload = json_decode((string) $job->payload, true) ?: [];

                return [
                    'id' => $job->id,
                    'uuid' => $job->uuid,
                    'queue' => $job->queue,
                    'name' => $payload['displayName'] ?? 'Failed job',
                    'status' => 'failed',
                    'exception' => mb_substr((string) $job->exception, 0, 500),
                    'failed_at' => $job->failed_at,
                ];
            })->all();
        }

        return response()->json([
            'data' => ['pending' => $pending, 'failed' => $failed],
            'meta' => [
                'pending_count' => count($pending),
                'failed_count' => count($failed),
                'queue_driver' => config('queue.default'),
            ],
        ]);
    }

    public function retry(string $id): JsonResponse
    {
        Artisan::call('queue:retry', ['id' => $id]);
        $this->activity->log('system.queue.retried', null, "Retried queue job {$id}.");

        return response()->json(['message' => 'Job queued for retry.']);
    }

    public function forget(string $id): JsonResponse
    {
        Artisan::call('queue:forget', ['id' => $id]);
        $this->activity->log('system.queue.forgotten', null, "Forgot queue job {$id}.");

        return response()->json(['message' => 'Failed job removed.']);
    }

    public function cancel(int $id): JsonResponse
    {
        abort_unless(Schema::hasTable('jobs'), 404, 'Queue table not found.');
        $deleted = DB::table('jobs')->where('id', $id)->whereNull('reserved_at')->delete();
        abort_if($deleted === 0, 422, 'Only pending, unreserved jobs can be cancelled.');
        $this->activity->log('system.queue.cancelled', null, "Cancelled queue job {$id}.");

        return response()->json(['message' => 'Pending job cancelled.']);
    }
}
