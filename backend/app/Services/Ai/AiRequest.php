<?php

namespace App\Services\Ai;

final class AiRequest
{
    public function __construct(
        public readonly array $messages,
        public readonly ?string $model = null,
        public readonly ?float $temperature = null,
        public readonly ?int $maxTokens = null,
        public readonly array $metadata = [],
    ) {
    }

    public static function chat(string $prompt, array $options = []): self
    {
        return new self(
            messages: [
                ['role' => 'user', 'content' => $prompt],
            ],
            model: $options['model'] ?? null,
            temperature: $options['temperature'] ?? null,
            maxTokens: $options['maxTokens'] ?? null,
            metadata: $options['metadata'] ?? [],
        );
    }

    public static function fromMessages(array $messages, array $options = []): self
    {
        return new self(
            messages: $messages,
            model: $options['model'] ?? null,
            temperature: $options['temperature'] ?? null,
            maxTokens: $options['maxTokens'] ?? null,
            metadata: $options['metadata'] ?? [],
        );
    }
}