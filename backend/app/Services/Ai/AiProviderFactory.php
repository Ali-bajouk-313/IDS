<?php

namespace App\Services\Ai;

use App\Services\Ai\Contracts\AiProviderInterface;
use App\Services\Ai\Exceptions\AiProviderUnavailableException;
use App\Services\Ai\Providers\AzureOpenAiProvider;
use App\Services\Ai\Providers\GroqProvider;
use App\Services\Ai\Providers\OllamaProvider;
use App\Services\Ai\Providers\OpenAiProvider;

class AiProviderFactory
{
    public function make(?string $provider = null): AiProviderInterface
    {
        return match ($this->resolveProviderName($provider)) {
            'openai' => new OpenAiProvider(),
            'azure_openai' => new AzureOpenAiProvider(),
            'groq' => new GroqProvider(),
            'ollama' => new OllamaProvider(),
            default => throw new AiProviderUnavailableException('AI provider [' . $this->resolveProviderName($provider) . '] is not supported.'),
        };
    }

    public function supportedProviders(): array
    {
        return ['openai', 'azure_openai', 'groq', 'ollama'];
    }

    public function resolveProviderName(?string $provider = null): string
    {
        return strtolower(trim($provider ?: (string) config('ai.provider', 'openai')));
    }
}