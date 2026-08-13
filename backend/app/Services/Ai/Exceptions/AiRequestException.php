<?php

namespace App\Services\Ai\Exceptions;

use Throwable;

class AiRequestException extends AiException
{
    public function __construct(
        string $message,
        public readonly ?int $statusCode = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode ?? 0, $previous);
    }
}