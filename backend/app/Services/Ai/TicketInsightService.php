<?php

namespace App\Services\Ai;

use App\Models\Ticket;
use Illuminate\Support\Arr;

class TicketInsightService
{
    public function __construct(protected AiService $aiService)
    {
    }

    public function summarizeTicket(array $ticketContext): array
    {
        return $this->generateInsight('summary', $ticketContext);
    }

    public function recommendPriority(array $ticketContext): array
    {
        return $this->generateInsight('priority', $ticketContext);
    }

    public function suggestTroubleshooting(array $ticketContext): array
    {
        return $this->generateInsight('troubleshooting', $ticketContext);
    }

    protected function generateInsight(string $mode, array $ticketContext): array
    {
        $response = $this->aiService->completeMessages(
            $this->buildMessages($mode, $ticketContext),
            null,
            [
                'metadata' => [
                    'feature' => $mode,
                    'ticketId' => Arr::get($ticketContext, 'ticket.id'),
                    'ticketNumber' => Arr::get($ticketContext, 'ticket.ticketNumber'),
                ],
            ]
        );

        $payload = $this->decodeJsonContent($response->content);

        return match ($mode) {
            'summary' => [
                'summary' => trim((string) ($payload['summary'] ?? $response->content)),
                'highlights' => $this->normalizeStringList($payload['highlights'] ?? $payload['keyPoints'] ?? []),
                'provider' => $response->provider,
                'model' => $response->model,
            ],
            'priority' => [
                'recommendedPriority' => $this->normalizePriority($payload['recommendedPriority'] ?? $payload['priority'] ?? $response->content),
                'explanation' => trim((string) ($payload['explanation'] ?? $payload['reason'] ?? $response->content)),
                'provider' => $response->provider,
                'model' => $response->model,
            ],
            'troubleshooting' => [
                'summary' => trim((string) ($payload['summary'] ?? $payload['analysis'] ?? '')),
                'suggestions' => $this->normalizeStringList($payload['suggestions'] ?? $payload['steps'] ?? []),
                'provider' => $response->provider,
                'model' => $response->model,
            ],
            default => [
                'content' => trim($response->content),
                'provider' => $response->provider,
                'model' => $response->model,
            ],
        };
    }

    protected function buildMessages(string $mode, array $ticketContext): array
    {
        $taskInstructions = match ($mode) {
            'summary' => 'Return JSON with keys summary and highlights. summary must be a concise professional paragraph, and highlights must be an array of short bullet-style points.',
            'priority' => 'Return JSON with keys recommendedPriority and explanation. recommendedPriority must be exactly one of Low, Medium, High, or Critical.',
            'troubleshooting' => 'Return JSON with keys summary and suggestions. suggestions must be an array of practical troubleshooting steps ordered from fastest to most involved.',
            default => 'Return JSON only.',
        };

        return [
            [
                'role' => 'system',
                'content' => 'You are HelpDeskPro AI. Use only the provided ticket data. Do not invent facts. Return valid JSON only. Do not wrap the JSON in markdown fences.',
            ],
            [
                'role' => 'user',
                'content' => "Task: {$taskInstructions}\n\nTicket data:\n" . json_encode($this->normalizeTicketContext($ticketContext), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            ],
        ];
    }

    protected function normalizeTicketContext(array $ticketContext): array
    {
        return [
            'ticket' => Arr::get($ticketContext, 'ticket', []),
            'comments' => array_values((array) Arr::get($ticketContext, 'comments', [])),
            'history' => array_values((array) Arr::get($ticketContext, 'history', [])),
        ];
    }

    protected function decodeJsonContent(string $content): array
    {
        $normalized = trim($content);
        $normalized = preg_replace('/^```(?:json)?\s*/i', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s*```$/', '', $normalized) ?? $normalized;

        $decoded = json_decode($normalized, true);

        return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [];
    }

    protected function normalizePriority(mixed $value): string
    {
        $priority = strtoupper(trim((string) $value));

        return match ($priority) {
            'LOW' => 'Low',
            'MEDIUM' => 'Medium',
            'HIGH' => 'High',
            'CRITICAL' => 'Critical',
            default => 'Medium',
        };
    }

    protected function normalizeStringList(mixed $value): array
    {
        if (is_string($value)) {
            $lines = preg_split('/\r\n|\r|\n/', trim($value)) ?: [];

            return array_values(array_filter(array_map(static function (string $line): string {
                return trim(preg_replace('/^[-*\d.\)\s]+/', '', $line) ?? $line);
            }, $lines)));
        }

        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(static function ($item): string {
            return trim((string) $item);
        }, $value)));
    }
}