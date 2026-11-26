<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiGenerationLog;
use App\Models\AiModelPrice;
use App\Models\AiPrompt;
use App\Models\AiProvider;
use App\Services\Admin\AdminActivityLogger;
use App\Services\Admin\AiCostService;
use App\Services\Ai\AiManager;
use App\Services\Ai\OpenAiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class AdminAiController extends Controller
{
    public function __construct(
        private readonly AdminActivityLogger $activity,
        private readonly AiCostService $costs,
        private readonly AiManager $ai,
    ) {
    }

    public function providers(): JsonResponse
    {
        $providers = AiProvider::query()->orderBy('priority')->get()->map(fn (AiProvider $provider) => $this->providerPayload($provider));

        return response()->json(['data' => $providers]);
    }

    public function storeProvider(Request $request): JsonResponse
    {
        $data = $this->providerData($request);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $provider = AiProvider::create($data);
        $this->activity->log('ai.provider.created', $provider, "Created AI provider {$provider->name}.");

        return response()->json(['data' => $this->providerPayload($provider)], 201);
    }

    public function updateProvider(Request $request, AiProvider $provider): JsonResponse
    {
        $data = $this->providerData($request, $provider);
        if (($data['api_key'] ?? '') === '') {
            unset($data['api_key']);
        }
        $provider->update($data);
        $this->activity->log('ai.provider.updated', $provider, "Updated AI provider {$provider->name}.");

        return response()->json(['data' => $this->providerPayload($provider->fresh())]);
    }

    public function destroyProvider(AiProvider $provider): JsonResponse
    {
        $name = $provider->name;
        $provider->delete();
        $this->activity->log('ai.provider.deleted', null, "Deleted AI provider {$name}.");

        return response()->json(['message' => 'AI provider deleted.']);
    }

    public function testProvider(AiProvider $provider): JsonResponse
    {
        $client = new OpenAiClient($provider);
        $started = microtime(true);

        try {
            $result = $client->chat([
                ['role' => 'system', 'content' => 'Reply with the single word OK.'],
                ['role' => 'user', 'content' => 'Connection test'],
            ], ['max_tokens' => 8]);

            return response()->json([
                'data' => [
                    'ok' => true,
                    'provider' => $provider->slug,
                    'model' => $result['model'] ?? $provider->model,
                    'latency_ms' => (int) round((microtime(true) - $started) * 1000),
                    'message' => 'Provider responded successfully.',
                ],
            ]);
        } catch (Throwable $error) {
            return response()->json([
                'data' => [
                    'ok' => false,
                    'provider' => $provider->slug,
                    'latency_ms' => (int) round((microtime(true) - $started) * 1000),
                    'message' => $error->getMessage(),
                ],
            ], 422);
        }
    }

    public function prompts(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'workflow' => ['nullable', 'string', 'max:60'],
            'status' => ['nullable', 'in:draft,published,archived'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $prompts = AiPrompt::query()
            ->with('creator:id,name')
            ->when($filters['q'] ?? null, fn ($query, $q) => $query->where('name', 'like', "%{$q}%"))
            ->when($filters['workflow'] ?? null, fn ($query, $workflow) => $query->where('workflow', $workflow))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 30));

        return response()->json([
            'data' => $prompts->items(),
            'meta' => ['current_page' => $prompts->currentPage(), 'last_page' => $prompts->lastPage(), 'total' => $prompts->total()],
        ]);
    }

    public function storePrompt(Request $request): JsonResponse
    {
        $data = $this->promptData($request);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']).'-'.Str::lower(Str::random(4));
        $data['created_by'] = $request->user()->id;
        $prompt = AiPrompt::create($data);
        $this->activity->log('ai.prompt.created', $prompt, "Created AI prompt {$prompt->name}.");

        return response()->json(['data' => $prompt->load('creator:id,name')], 201);
    }

    public function updatePrompt(Request $request, AiPrompt $prompt): JsonResponse
    {
        $data = $this->promptData($request, $prompt);
        if (isset($data['content']) && $data['content'] !== $prompt->content) {
            $data['version'] = $prompt->version + 1;
        }
        $prompt->update($data);
        $this->activity->log('ai.prompt.updated', $prompt, "Updated AI prompt {$prompt->name}.", ['version' => $prompt->version]);

        return response()->json(['data' => $prompt->fresh()->load('creator:id,name')]);
    }

    public function destroyPrompt(AiPrompt $prompt): JsonResponse
    {
        $name = $prompt->name;
        $prompt->delete();
        $this->activity->log('ai.prompt.deleted', null, "Deleted AI prompt {$name}.");

        return response()->json(['message' => 'Prompt deleted.']);
    }

    public function testPrompt(Request $request, AiPrompt $prompt): JsonResponse
    {
        $variables = $request->validate(['variables' => ['nullable', 'array']])['variables'] ?? [];
        $content = $prompt->content;
        foreach ($variables as $key => $value) {
            $content = str_replace(['{{'.$key.'}}', '{'.$key.'}'], (string) $value, $content);
        }

        $client = $this->ai->client();
        if ($client === null) {
            return response()->json(['message' => 'No active AI provider is configured.'], 503);
        }

        try {
            $result = $client->chat([
                ['role' => 'system', 'content' => $content],
                ['role' => 'user', 'content' => 'Return a concise JSON test result with a key named ok.'],
            ], ['max_tokens' => 200]);

            return response()->json(['data' => ['ok' => true, 'content' => $result['content'], 'tokens_in' => $result['tokens_in'], 'tokens_out' => $result['tokens_out'], 'model' => $result['model']]]);
        } catch (Throwable $error) {
            return response()->json(['message' => $error->getMessage()], 422);
        }
    }

    public function generations(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['nullable', 'string', 'max:24'],
            'purpose' => ['nullable', 'string', 'max:60'],
            'provider' => ['nullable', 'string', 'max:40'],
            'review_status' => ['nullable', 'string', 'max:24'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $logs = AiGenerationLog::query()
            ->with('user:id,name,email')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['purpose'] ?? null, fn ($query, $purpose) => $query->where('purpose', $purpose))
            ->when($filters['provider'] ?? null, fn ($query, $provider) => $query->where('provider', $provider))
            ->when($filters['review_status'] ?? null, fn ($query, $status) => $query->where('review_status', $status))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->where('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->where('created_at', '<=', $to))
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 30));

        return response()->json([
            'data' => $logs->items(),
            'meta' => ['current_page' => $logs->currentPage(), 'last_page' => $logs->lastPage(), 'total' => $logs->total()],
        ]);
    }

    public function generation(AiGenerationLog $generation): JsonResponse
    {
        return response()->json(['data' => $generation->load(['user:id,name,email', 'reviewer:id,name'])]);
    }

    public function reviewGeneration(Request $request, AiGenerationLog $generation): JsonResponse
    {
        $data = $request->validate([
            'review_status' => ['required', 'in:approved,rejected,needs_regeneration'],
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $generation->update([
            'review_status' => $data['review_status'],
            'review_notes' => $data['review_notes'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);
        $this->activity->log('ai.generation.reviewed', $generation, "Reviewed AI generation #{$generation->id} as {$data['review_status']}.");

        return response()->json(['data' => $generation->fresh(['user:id,name,email', 'reviewer:id,name'])]);
    }

    public function jobs(Request $request): JsonResponse
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
                    'available_at' => $job->available_at,
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
            'meta' => ['pending_count' => count($pending), 'failed_count' => count($failed), 'queue_driver' => config('queue.default')],
        ]);
    }

    public function retryJob(string $id): JsonResponse
    {
        Artisan::call('queue:retry', ['id' => $id]);
        $this->activity->log('ai.job.retried', null, "Retried failed queue job {$id}.");

        return response()->json(['message' => 'Job queued for retry.']);
    }

    public function forgetJob(string $id): JsonResponse
    {
        Artisan::call('queue:forget', ['id' => $id]);
        $this->activity->log('ai.job.forgotten', null, "Forgot failed queue job {$id}.");

        return response()->json(['message' => 'Failed job removed.']);
    }

    public function cancelJob(int $id): JsonResponse
    {
        abort_unless(Schema::hasTable('jobs'), 404, 'Queue table not found.');
        $deleted = DB::table('jobs')->where('id', $id)->whereNull('reserved_at')->delete();
        abort_if($deleted === 0, 422, 'Only pending, unreserved jobs can be cancelled.');
        $this->activity->log('ai.job.cancelled', null, "Cancelled pending queue job {$id}.");

        return response()->json(['message' => 'Pending job cancelled.']);
    }

    public function costs(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $from = isset($data['from']) ? \Carbon\Carbon::parse($data['from']) : now()->subDays(29);
        $to = isset($data['to']) ? \Carbon\Carbon::parse($data['to']) : now();

        return response()->json([
            'data' => [
                'summary' => $this->costs->summary($from, $to),
                'prices' => AiModelPrice::query()->orderBy('provider')->orderBy('model')->get(),
                'providers' => AiProvider::query()->get(['id', 'name', 'slug', 'model', 'image_model']),
            ],
        ]);
    }

    public function storePrice(Request $request): JsonResponse
    {
        $price = AiModelPrice::create($this->priceData($request));
        $this->activity->log('ai.price.created', $price, "Created AI price for {$price->provider}/{$price->model}.");

        return response()->json(['data' => $price], 201);
    }

    public function updatePrice(Request $request, AiModelPrice $price): JsonResponse
    {
        $price->update($this->priceData($request, $price));
        $this->activity->log('ai.price.updated', $price, "Updated AI price for {$price->provider}/{$price->model}.");

        return response()->json(['data' => $price->fresh()]);
    }

    public function destroyPrice(AiModelPrice $price): JsonResponse
    {
        $price->delete();

        return response()->json(['message' => 'Price removed.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function providerData(Request $request, ?AiProvider $provider = null): array
    {
        return $request->validate([
            'name' => [$provider ? 'sometimes' : 'required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:80', 'alpha_dash', 'unique:ai_providers,slug'.($provider ? ','.$provider->id : '')],
            'driver' => ['sometimes', 'in:openai,compatible'],
            'model' => ['nullable', 'string', 'max:120'],
            'image_model' => ['nullable', 'string', 'max:120'],
            'base_url' => ['nullable', 'url', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
            'priority' => ['sometimes', 'integer', 'min:0', 'max:1000'],
            'meta' => ['nullable', 'array'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function promptData(Request $request, ?AiPrompt $prompt = null): array
    {
        return $request->validate([
            'name' => [$prompt ? 'sometimes' : 'required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:120', 'alpha_dash', 'unique:ai_prompts,slug'.($prompt ? ','.$prompt->id : '')],
            'category' => [$prompt ? 'sometimes' : 'required', 'string', 'max:60'],
            'workflow' => [$prompt ? 'sometimes' : 'required', 'string', 'max:60'],
            'content' => [$prompt ? 'sometimes' : 'required', 'string', 'max:50000'],
            'variables' => ['nullable', 'array'],
            'status' => ['sometimes', 'in:draft,published,archived'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function priceData(Request $request, ?AiModelPrice $price = null): array
    {
        return $request->validate([
            'provider' => [$price ? 'sometimes' : 'required', 'string', 'max:40'],
            'model' => [$price ? 'sometimes' : 'required', 'string', 'max:120'],
            'input_per_million' => ['sometimes', 'numeric', 'min:0', 'max:100000'],
            'output_per_million' => ['sometimes', 'numeric', 'min:0', 'max:100000'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function providerPayload(AiProvider $provider): array
    {
        return [
            'id' => $provider->id,
            'name' => $provider->name,
            'slug' => $provider->slug,
            'driver' => $provider->driver,
            'model' => $provider->model,
            'image_model' => $provider->image_model,
            'base_url' => $provider->base_url,
            'is_active' => $provider->is_active,
            'priority' => $provider->priority,
            'meta' => $provider->meta,
            'has_api_key' => ! empty($provider->api_key),
            'created_at' => $provider->created_at,
            'updated_at' => $provider->updated_at,
        ];
    }
}
