<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\AiRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class GeminiProvider extends AbstractAiProvider
{
    public function name(): string
    {
        return 'gemini';
    }

    protected function isConfigured(): bool
    {
        return trim((string) config('ai.providers.gemini.api_key', '')) !== '';
    }

    protected function endpoint(AiRequest $request): string
    {
        $model = $this->resolveModel($request);
        $baseUrl = rtrim((string) config('ai.providers.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');

        return $baseUrl . '/models/' . rawurlencode($model) . ':generateContent';
    }

    protected function headers(): array
    {
        return [];
    }

    protected function payload(AiRequest $request): array
    {
        $contents = [];
        foreach ($request->messages as $message) {
            $role = ($message['role'] ?? 'user') === 'assistant' ? 'model' : 'user';
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => (string) ($message['content'] ?? '')]],
            ];
        }

        return [
            'contents' => $contents,
            'generationConfig' => $this->filteredPayload([
                'temperature' => $this->temperature($request),
                'maxOutputTokens' => $this->maxTokens($request),
            ]),
        ];
    }

    protected function extractContent(array $data): string
    {
        return (string) data_get($data, 'candidates.0.content.parts.0.text', '');
    }

    protected function send(AiRequest $request): Response
    {
        return Http::timeout($this->timeout())
            ->acceptJson()
            ->asJson()
            ->withHeaders($this->headers())
            ->withQueryParameters(['key' => config('ai.providers.gemini.api_key')])
            ->post($this->endpoint($request), $this->payload($request));
    }
}