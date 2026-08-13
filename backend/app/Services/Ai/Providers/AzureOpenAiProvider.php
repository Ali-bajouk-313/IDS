<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\AiRequest;

class AzureOpenAiProvider extends AbstractAiProvider
{
    public function name(): string
    {
        return 'azure_openai';
    }

    protected function isConfigured(): bool
    {
        return is_string(config('ai.providers.azure_openai.api_key')) && config('ai.providers.azure_openai.api_key') !== ''
            && is_string(config('ai.providers.azure_openai.endpoint')) && config('ai.providers.azure_openai.endpoint') !== ''
            && is_string(config('ai.providers.azure_openai.deployment')) && config('ai.providers.azure_openai.deployment') !== '';
    }

    protected function endpoint(AiRequest $request): string
    {
        $endpoint = rtrim((string) config('ai.providers.azure_openai.endpoint'), '/');
        $deployment = rawurlencode((string) config('ai.providers.azure_openai.deployment'));
        $apiVersion = rawurlencode((string) config('ai.providers.azure_openai.api_version', '2024-10-21'));

        return $endpoint . '/openai/deployments/' . $deployment . '/chat/completions?api-version=' . $apiVersion;
    }

    protected function headers(): array
    {
        return [
            'api-key' => (string) config('ai.providers.azure_openai.api_key'),
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