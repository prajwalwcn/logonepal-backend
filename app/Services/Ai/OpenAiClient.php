<?php

namespace App\Services\Ai;

use App\Models\AiProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * OpenAI compatible client. Also works with any provider exposing the
 * /chat/completions and /images/generations endpoints by changing the base URL.
 */
class OpenAiClient implements LlmClient
{
    public function __construct(private readonly ?AiProvider $provider = null)
    {
    }

    public function isConfigured(): bool
    {
        return $this->apiKey() !== null;
    }

    public function chat(array $messages, array $options = []): array
    {
        $apiKey = $this->apiKey();

        if ($apiKey === null) {
            throw new RuntimeException('AI provider is not configured.');
        }

        $model = $options['model'] ?? $this->provider?->model ?? config('services.openai.model', 'gpt-4o-mini');

        $response = Http::withToken($apiKey)
            ->timeout((int) config('services.openai.timeout', 25))
            ->acceptJson()
            ->post($this->baseUrl().'/chat/completions', [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $options['temperature'] ?? 0.7,
                'max_tokens' => $options['max_tokens'] ?? (int) config('services.openai.max_tokens', 900),
                'response_format' => ['type' => 'json_object'],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('AI provider error: '.$response->status().' '.mb_substr($response->body(), 0, 400));
        }

        $payload = $response->json();
        $content = $payload['choices'][0]['message']['content'] ?? null;

        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('AI provider returned an empty completion.');
        }

        return [
            'content' => $content,
            'tokens_in' => (int) ($payload['usage']['prompt_tokens'] ?? 0),
            'tokens_out' => (int) ($payload['usage']['completion_tokens'] ?? 0),
            'model' => $payload['model'] ?? $model,
            'provider' => $this->provider?->slug ?? 'openai',
        ];
    }

    public function generateImage(string $prompt, array $options = []): array
    {
        $apiKey = $this->apiKey();

        if ($apiKey === null) {
            throw new RuntimeException('AI image provider is not configured.');
        }

        $model = $options['model'] ?? $this->provider?->image_model ?? config('services.openai.image_model', 'gpt-image-1');

        $response = Http::withToken($apiKey)
            ->timeout((int) config('services.openai.timeout', 25) * 2)
            ->acceptJson()
            ->post($this->baseUrl().'/images/generations', [
                'model' => $model,
                'prompt' => $prompt,
                'n' => 1,
                'size' => $options['size'] ?? '1024x1024',
                'background' => 'transparent',
            ]);

        if ($response->failed()) {
            throw new RuntimeException('AI image provider error: '.$response->status().' '.mb_substr($response->body(), 0, 400));
        }

        $data = $response->json('data', []);
        $urls = [];

        foreach ($data as $item) {
            if (isset($item['url'])) {
                $urls[] = $item['url'];
            } elseif (isset($item['b64_json'])) {
                $urls[] = 'data:image/png;base64,'.$item['b64_json'];
            }
        }

        if ($urls === []) {
            throw new RuntimeException('AI image provider returned no images.');
        }

        return ['urls' => $urls, 'provider' => $this->provider?->slug ?? 'openai'];
    }

    private function baseUrl(): string
    {
        return rtrim($this->provider?->base_url ?: (string) config('services.openai.base_url', 'https://api.openai.com/v1'), '/');
    }

    private function apiKey(): ?string
    {
        $key = $this->provider?->api_key ?: config('services.openai.key');

        return is_string($key) && trim($key) !== '' ? $key : null;
    }
}
