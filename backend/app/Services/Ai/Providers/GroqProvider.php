<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\AiRequest;

class GroqProvider extends AbstractAiProvider
{
    public function name(): string
    {
        return 'groq';
    }

    protected function isConfigured(): bool
    {
        $apiKey = trim((string) config('ai.providers.groq.api_key', ''));

        return $apiKey !== '';
    }

    protected function endpoint(AiRequest $request): string
    {
        return rtrim((string) config('ai.providers.groq.base_url', 'https://api.groq.com/openai/v1'), '/') . '/chat/completions';
    }

    protected function headers(): array
    {
        $apiKey = trim((string) config('ai.providers.groq.api_key', ''));

        return [
            'Authorization' => 'Bearer ' . $apiKey,
        ];
    }

    protected function payload(AiRequest $request): array
    {
        return $this->filteredPayload([
            'model' => $this->resolveModel($request),
            'messages' => $request->messages,
            'temperature' => $this->temperature($request),
            'max_tokens' => $this->maxTokens($request),
            'stream' => false,
        ]);
    }

    protected function resolveModel(AiRequest $request): string
    {
        return (string) ($request->model ?? config('ai.providers.groq.model', config('ai.model', 'llama-3.1-8b-instant')));
    }

    protected function extractContent(array $data): string
    {
        return (string) data_get($data, 'choices.0.message.content', '');
    }
}
