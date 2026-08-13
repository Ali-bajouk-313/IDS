<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\AiRequest;
use Illuminate\Support\Facades\Log;

class OpenAiProvider extends AbstractAiProvider
{
    public function name(): string
    {
        return 'openai';
    }

    protected function isConfigured(): bool
    {
        $apiKey = trim((string) config('ai.providers.openai.api_key', ''));

        return $apiKey !== '';
    }

    protected function endpoint(AiRequest $request): string
    {
        return rtrim((string) config('ai.providers.openai.base_url', 'https://api.openai.com/v1'), '/') . '/chat/completions';
    }

    protected function headers(): array
    {
        $apiKey = trim((string) config('ai.providers.openai.api_key', ''));

        Log::info('OpenAI request diagnostics.', [
            'provider' => 'openai',
            'api_key_configured' => $apiKey !== '',
            'api_key_length' => strlen($apiKey),
            'model' => (string) config('ai.providers.openai.model', config('ai.model', 'gpt-4o-mini')),
            'base_url' => rtrim((string) config('ai.providers.openai.base_url', 'https://api.openai.com/v1'), '/'),
            'authorization_header_configured' => $apiKey !== '',
        ]);

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

    protected function extractContent(array $data): string
    {
        return (string) data_get($data, 'choices.0.message.content', '');
    }
}