<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\AiRequest;

class OllamaProvider extends AbstractAiProvider
{
    public function name(): string
    {
        return 'ollama';
    }

    protected function isConfigured(): bool
    {
        return is_string(config('ai.providers.ollama.base_url')) && config('ai.providers.ollama.base_url') !== '';
    }

    protected function endpoint(AiRequest $request): string
    {
        return rtrim((string) config('ai.providers.ollama.base_url', 'http://127.0.0.1:11434'), '/') . '/api/chat';
    }

    protected function headers(): array
    {
        return [];
    }

    protected function payload(AiRequest $request): array
    {
        return $this->filteredPayload([
            'model' => $this->resolveModel($request),
            'messages' => $request->messages,
            'stream' => false,
            'options' => $this->filteredPayload([
                'temperature' => $this->temperature($request),
                'num_predict' => $this->maxTokens($request),
            ]),
        ]);
    }

    protected function extractContent(array $data): string
    {
        return (string) (data_get($data, 'message.content') ?? data_get($data, 'response', ''));
    }
}