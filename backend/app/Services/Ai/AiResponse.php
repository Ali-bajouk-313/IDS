<?php

namespace App\Services\Ai;

final class AiResponse
{
    public function __construct(
        public readonly string $provider,
        public readonly string $model,
        public readonly string $content,
        public readonly array $raw = [],
        public readonly array $usage = [],
    ) {
    }
}