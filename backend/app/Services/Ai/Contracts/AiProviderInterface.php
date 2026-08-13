<?php

namespace App\Services\Ai\Contracts;

use App\Services\Ai\AiRequest;
use App\Services\Ai\AiResponse;

interface AiProviderInterface
{
    public function name(): string;

    public function complete(AiRequest $request): AiResponse;
}