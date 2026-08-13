<?php

namespace App\Services\Ai;

use App\Services\Ai\AiRequest;
use App\Services\Ai\AiResponse;
use App\Services\Ai\Contracts\AiProviderInterface;

class AiService
{
    public function __construct(protected AiProviderFactory $factory)
    {
    }

    public function complete(AiRequest $request, ?string $provider = null): AiResponse
    {
        return $this->provider($provider)->complete($request);
    }

    public function completePrompt(string $prompt, ?string $provider = null, array $options = []): AiResponse
    {
        return $this->complete(AiRequest::chat($prompt, $options), $provider);
    }

    public function completeMessages(array $messages, ?string $provider = null, array $options = []): AiResponse
    {
        return $this->complete(AiRequest::fromMessages($messages, $options), $provider);
    }

    public function currentProvider(): string
    {
        return $this->factory->resolveProviderName();
    }

    protected function provider(?string $provider = null): AiProviderInterface
    {
        return $this->factory->make($provider);
    }
}