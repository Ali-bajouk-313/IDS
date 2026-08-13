<?php

namespace App\Services\Ai;

use App\Models\Category;
use App\Models\Ticket;
use App\Models\TicketComment;

class KnowledgeBaseAssistantService
{
    public function __construct(protected AiService $aiService)
    {
    }

    public function askQuestion(string $question): array
    {
        $normalizedQuestion = trim(preg_replace('/\s+/', ' ', $question) ?? $question);
        $context = $this->buildContextForQuestion($normalizedQuestion);

        $response = $this->aiService->completeMessages(
            $this->buildMessages($normalizedQuestion, $context),
            null,
            [
                'metadata' => [
                    'feature' => 'knowledge_base',
                    'question' => mb_substr($normalizedQuestion, 0, 160),
                ],
            ]
        );

        $payload = $this->decodeJsonContent($response->content);

        return [
            'answer' => trim((string) ($payload['answer'] ?? $response->content)),
            'troubleshootingSteps' => $this->normalizeStringList($payload['troubleshootingSteps'] ?? $payload['steps'] ?? []),
            'sourceContext' => $context,
            'provider' => $response->provider,
            'model' => $response->model,
        ];
    }

    public function buildContextForQuestion(string $question): array
    {
        $normalizedQuestion = trim(preg_replace('/\s+/', ' ', $question) ?? $question);
        $keywords = $this->extractKeywords($normalizedQuestion);

        return $this->buildContext($normalizedQuestion, $keywords);
    }

    protected function buildMessages(string $question, array $context): array
    {
        return [
            [
                'role' => 'system',
                'content' => 'You are HelpDeskPro Knowledge Base Assistant. Answer technical questions using the provided internal context first. If the context is weak or incomplete, give the best concise IT guidance and clearly say that no exact internal match was found. Return valid JSON only. Do not wrap the JSON in markdown fences.',
            ],
            [
                'role' => 'user',
                'content' => 'Question: ' . $question . "\n\nInternal context:\n" . json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            ],
        ];
    }

    protected function buildContext(string $question, array $keywords): array
    {
        $categories = $this->findRelevantCategories($keywords);
        $tickets = $this->findRelevantTickets($keywords, $categories);
        $comments = $this->findRelevantComments($tickets);

        return [
            'question' => $question,
            'keywords' => $keywords,
            'categories' => $categories,
            'tickets' => $tickets,
            'comments' => $comments,
        ];
    }

    protected function findRelevantCategories(array $keywords): array
    {
        $query = Category::query()->select(['id', 'categoryName', 'description'])->orderByDesc('createdAt');

        if (!empty($keywords)) {
            $query->where(function ($categoryQuery) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $categoryQuery->orWhere('categoryName', 'like', '%' . $keyword . '%')
                        ->orWhere('description', 'like', '%' . $keyword . '%');
                }
            });
        }

        return $query->limit(3)
            ->get()
            ->map(function (Category $category): array {
                return [
                    'id' => $category->id,
                    'name' => $category->categoryName,
                    'description' => $category->description,
                ];
            })
            ->values()
            ->all();
    }

    protected function findRelevantTickets(array $keywords, array $categories): array
    {
        $categoryNames = collect($categories)->pluck('name')->filter()->values()->all();
        $query = Ticket::query()
            ->with(['category:id,categoryName'])
            ->whereIn('status', ['Resolved', 'Closed'])
            ->orderByDesc('updatedAt');

        if (!empty($keywords) || !empty($categoryNames)) {
            $query->where(function ($ticketQuery) use ($keywords, $categoryNames) {
                foreach ($keywords as $keyword) {
                    $ticketQuery->orWhere('title', 'like', '%' . $keyword . '%')
                        ->orWhere('description', 'like', '%' . $keyword . '%');
                }

                foreach ($categoryNames as $categoryName) {
                    $ticketQuery->orWhereHas('category', function ($categoryQuery) use ($categoryName) {
                        $categoryQuery->where('categoryName', 'like', '%' . $categoryName . '%');
                    });
                }
            });
        }

        return $query->limit(3)
            ->get()
            ->map(function (Ticket $ticket): array {
                return [
                    'id' => $ticket->id,
                    'ticketNumber' => $ticket->ticketNumber,
                    'title' => $ticket->title,
                    'status' => $ticket->status,
                    'priority' => $ticket->priority,
                    'category' => $ticket->category?->categoryName ?? 'Uncategorized',
                ];
            })
            ->values()
            ->all();
    }

    protected function findRelevantComments(array $tickets): array
    {
        $ticketIds = collect($tickets)->pluck('id')->filter()->values()->all();

        if (empty($ticketIds)) {
            return [];
        }

        return TicketComment::query()
            ->with(['user:id,fullName', 'ticket:id,ticketNumber'])
            ->whereIn('ticketId', $ticketIds)
            ->orderByDesc('createdAt')
            ->limit(4)
            ->get()
            ->map(function (TicketComment $comment): array {
                return [
                    'ticketId' => $comment->ticket?->id,
                    'ticketNumber' => $comment->ticket?->ticketNumber,
                    'author' => $comment->user?->fullName ?? 'Unknown user',
                    'commentText' => $this->truncateText($comment->commentText, 180),
                ];
            })
            ->values()
            ->all();
    }

    protected function extractKeywords(string $question): array
    {
        $tokens = preg_split('/[^a-zA-Z0-9]+/', strtolower($question)) ?: [];
        $stopWords = [
            'the', 'and', 'for', 'with', 'that', 'this', 'from', 'have', 'has', 'what', 'when', 'where', 'which', 'how',
            'whats', 'should', 'could', 'would', 'will', 'does', 'done', 'into', 'your', 'you', 'are', 'can', 'need', 'help',
            'please', 'issue', 'problem', 'ticket', 'assistant', 'base', 'knowledge', 'question', 'work', 'about', 'why', 'been',
        ];

        return collect($tokens)
            ->filter(fn (string $token) => strlen($token) >= 3)
            ->reject(fn (string $token) => in_array($token, $stopWords, true))
            ->unique()
            ->take(6)
            ->values()
            ->all();
    }

    protected function decodeJsonContent(string $content): array
    {
        $normalized = trim($content);
        $normalized = preg_replace('/^```(?:json)?\s*/i', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s*```$/', '', $normalized) ?? $normalized;

        $decoded = json_decode($normalized, true);

        return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [];
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

    protected function truncateText(?string $text, int $limit): ?string
    {
        if ($text === null) {
            return null;
        }

        $clean = trim(preg_replace('/\s+/', ' ', $text) ?? $text);

        if (mb_strlen($clean) <= $limit) {
            return $clean;
        }

        return rtrim(mb_substr($clean, 0, $limit - 1)) . '…';
    }
}