<?php

namespace App\Services\Ai;

use App\Models\AiProvider;
use Throwable;

class AiManager
{
    public function client(string $purpose = 'text'): ?LlmClient
    {
        if (! config('services.ai.enabled')) {
            return null;
        }

        try {
            $provider = AiProvider::query()
                ->where('is_active', true)
                ->orderBy('priority')
                ->first();
        } catch (Throwable) {
            $provider = null;
        }

        if ($provider !== null) {
            return match ($provider->driver) {
                'openai', 'compatible' => new OpenAiClient($provider),
                default => null,
            };
        }

        if (config('services.openai.key')) {
            return new OpenAiClient();
        }

        return null;
    }

    public function enabled(): bool
    {
        return $this->client() !== null;
    }
}
