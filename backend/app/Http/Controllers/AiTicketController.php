<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Services\Ai\Exceptions\AiProviderUnavailableException;
use App\Services\Ai\Exceptions\AiRequestException;
use App\Services\Ai\TicketInsightService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class AiTicketController extends Controller
{
    public function __construct(protected TicketInsightService $ticketInsightService)
    {
    }

    public function summary(Request $request, $ticket)
    {
        return $this->generateInsight($request, $ticket, 'summary');
    }

    public function priority(Request $request, $ticket)
    {
        return $this->generateInsight($request, $ticket, 'priority');
    }

    public function troubleshooting(Request $request, $ticket)
    {
        return $this->generateInsight($request, $ticket, 'troubleshooting');
    }

    protected function generateInsight(Request $request, $ticketId, string $mode)
    {
        $user = auth('api')->user();

        if (!$user || !$user->role) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $ticket = Ticket::with(['category:id,categoryName', 'creator:id,fullName', 'assignedUser:id,fullName'])->find($ticketId);

        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found'], 404);
        }

        if (!$this->canViewTicket($user, $ticket)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $ticketContext = $this->buildTicketContext($ticket);

        try {
            $result = match ($mode) {
                'summary' => $this->ticketInsightService->summarizeTicket($ticketContext),
                'priority' => $this->ticketInsightService->recommendPriority($ticketContext),
                'troubleshooting' => $this->ticketInsightService->suggestTroubleshooting($ticketContext),
                default => throw new \InvalidArgumentException('Unsupported AI insight mode.'),
            };

            return response()->json([
                'success' => true,
                'data' => array_merge([
                    'ticketId' => $ticket->id,
                    'ticketNumber' => $ticket->ticketNumber,
                    'title' => $ticket->title,
                    'category' => $ticket->category?->categoryName,
                    'priority' => $ticket->priority,
                    'status' => $ticket->status,
                    'generatedAt' => now()->toIso8601String(),
                ], $result),
            ]);
        } catch (AiProviderUnavailableException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        } catch (AiRequestException $exception) {
            $status = $exception->statusCode && $exception->statusCode >= 400 && $exception->statusCode <= 599
                ? $exception->statusCode
                : 502;

            return response()->json(['message' => $exception->getMessage()], $status);
        } catch (Throwable $exception) {
            return response()->json(['message' => 'Unable to generate AI insight.'], 500);
        }
    }

    protected function buildTicketContext(Ticket $ticket): array
    {
        $comments = TicketComment::with('user:id,fullName')
            ->where('ticketId', $ticket->id)
            ->orderByDesc('createdAt')
            ->limit(5)
            ->get()
            ->map(function (TicketComment $comment): array {
                return [
                    'author' => $comment->user?->fullName ?? 'Unknown user',
                    'commentText' => $comment->commentText,
                    'createdAt' => $comment->createdAt?->toIso8601String(),
                ];
            })
            ->values()
            ->all();

        $history = DB::table('tickethistory')
            ->leftJoin('users', 'tickethistory.changedBy', '=', 'users.id')
            ->select('tickethistory.*', 'users.fullName as userName')
            ->where('ticketId', $ticket->id)
            ->orderByDesc('changedAt')
            ->limit(5)
            ->get()
            ->map(function ($entry): array {
                return [
                    'author' => $entry->userName ?? 'Unknown user',
                    'oldStatus' => $entry->oldStatus,
                    'newStatus' => $entry->newStatus,
                    'comment' => $entry->comment,
                    'changedAt' => $entry->changedAt,
                ];
            })
            ->values()
            ->all();

        return [
            'ticket' => [
                'id' => $ticket->id,
                'ticketNumber' => $ticket->ticketNumber,
                'title' => $ticket->title,
                'description' => $ticket->description,
                'category' => $ticket->category?->categoryName ?? 'Uncategorized',
                'priority' => $ticket->priority,
                'status' => $ticket->status,
                'creator' => $ticket->creator?->fullName ?? 'Unknown user',
                'assignedTo' => $ticket->assignedUser?->fullName ?? $ticket->assignedSupportName ?? 'Unassigned',
            ],
            'comments' => $comments,
            'history' => $history,
        ];
    }

    protected function canViewTicket($user, Ticket $ticket): bool
    {
        return match ($user->role->roleName) {
            'Admin' => true,
            'Manager' => $ticket->createdBy === $user->id,
            'IT Support' => $ticket->assignedTo === $user->id,
            default => $ticket->createdBy === $user->id,
        };
    }
}