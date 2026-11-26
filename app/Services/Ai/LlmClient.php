<?php

namespace App\Services\Ai;

interface LlmClient
{
    public function isConfigured(): bool;

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array{content: string, tokens_in: int, tokens_out: int, model: string, provider: string}
     *
     * @throws \RuntimeException when the provider cannot be reached or returns an invalid payload
     */
    public function chat(array $messages, array $options = []): array;

    /**
     * @return array{urls: array<int, string>, provider: string}
     *
     * @throws \RuntimeException
     */
    public function generateImage(string $prompt, array $options = []): array;
}
