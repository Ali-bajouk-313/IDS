<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\AiRequest;
use App\Services\Ai\AiResponse;
use App\Services\Ai\Contracts\AiProviderInterface;
use App\Services\Ai\Exceptions\AiProviderUnavailableException;
use App\Services\Ai\Exceptions\AiRequestException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

abstract class AbstractAiProvider implements AiProviderInterface
{
    public function complete(AiRequest $request): AiResponse
    {
        $this->ensureAvailable();

        try {
            $response = Http::timeout($this->timeout())
                ->acceptJson()
                ->asJson()
                ->withHeaders($this->headers())
                ->post($this->endpoint($request), $this->payload($request));
        } catch (ConnectionException $exception) {
            throw new AiRequestException('AI provider request timed out or could not connect.', previous: $exception);
        }

        if (!$response->successful()) {
            throw new AiRequestException($this->errorMessage($response), $response->status());
        }

        $data = $response->json();
        $data = is_array($data) ? $data : [];

        return new AiResponse(
            provider: $this->name(),
            model: $this->resolveModel($request),
            content: $this->extractContent($data),
            raw: $data,
            usage: $this->extractUsage($data),
        );
    }

    protected function timeout(): int
    {
        return (int) config('ai.timeout', 30);
    }

    protected function temperature(AiRequest $request): float
    {
        return (float) ($request->temperature ?? config('ai.temperature', 0.2));
    }

    protected function maxTokens(AiRequest $request): ?int
    {
        $value = $request->maxTokens ?? config('ai.max_tokens');

        return $value !== null ? (int) $value : null;
    }

    protected function resolveModel(AiRequest $request): string
    {
        return (string) ($request->model ?? config('ai.model', 'gpt-4o-mini'));
    }

    protected function filteredPayload(array $payload): array
    {
        return array_filter($payload, static fn ($value) => $value !== null);
    }

    protected function extractUsage(array $data): array
    {
        return (array) data_get($data, 'usage', []);
    }

    protected function errorMessage(Response $response): string
    {
        $data = $response->json();

        if (is_array($data)) {
            $message = data_get($data, 'error.message');

            if (is_string($message) && $message !== '') {
                return $message;
            }
        }

        return 'AI provider [' . $this->name() . '] returned an unexpected response.';
    }

    protected function ensureAvailable(): void
    {
        if (!$this->isConfigured()) {
            throw new AiProviderUnavailableException('AI provider [' . $this->name() . '] is not configured.');
        }
    }

    abstract public function name(): string;

    abstract protected function isConfigured(): bool;

    abstract protected function endpoint(AiRequest $request): string;

    abstract protected function headers(): array;

    abstract protected function payload(AiRequest $request): array;

    abstract protected function extractContent(array $data): string;
}