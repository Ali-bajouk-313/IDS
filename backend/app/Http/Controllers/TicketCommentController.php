<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use App\Services\TicketHistoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TicketCommentController extends Controller
{
    public function __construct(
        protected TicketHistoryService $ticketHistoryService,
        protected ActivityLogService $activityLogService,
        protected NotificationService $notificationService
    )
    {
    }

    public function index($ticket)
    {
        $user = auth('api')->user();
        $ticketRecord = Ticket::with('creator')->find($ticket);

        if (!$ticketRecord) {
            return response()->json(['message' => 'Ticket not found'], 404);
        }

        if (!$this->canViewTicket($user, $ticketRecord)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $comments = TicketComment::with(['user:id,fullName'])
            ->where('ticketId', $ticketRecord->id)
            ->orderBy('createdAt', 'asc')
            ->get()
            ->map(function (TicketComment $comment) {
                return [
                    'id' => $comment->id,
                    'commentText' => $comment->commentText,
                    'user' => [
                        'id' => $comment->user?->id,
                        'fullName' => $comment->user?->fullName,
                    ],
                    'createdAt' => $comment->createdAt,
                ];
            })
            ->values();

        return response()->json(['comments' => $comments]);
    }

    public function store(Request $request, $ticket)
    {
        $user = auth('api')->user();
        $ticketRecord = Ticket::with('creator')->find($ticket);

        if (!$ticketRecord) {
            return response()->json(['message' => 'Ticket not found'], 404);
        }

        if (!$this->canCommentOnTicket($user, $ticketRecord)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'commentText' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $commentText = trim($request->commentText);

        $comment = TicketComment::create([
            'ticketId' => $ticketRecord->id,
            'userId' => $user->id,
            'commentText' => $commentText,
            'createdAt' => now(),
        ]);

        $this->ticketHistoryService->recordCommentAdded($ticketRecord, $user);
        $this->activityLogService->logCommentAdded($user, $ticketRecord, $request->ip());

        $recipients = [];
        if ($ticketRecord->createdBy) {
            $creator = \App\Models\User::find($ticketRecord->createdBy);
            if ($creator && $creator->id !== $user->id) {
                $recipients[] = $creator;
            }
        }
        if ($ticketRecord->assignedTo) {
            $assignee = \App\Models\User::find($ticketRecord->assignedTo);
            if ($assignee && $assignee->id !== $user->id) {
                $recipients[] = $assignee;
            }
        }

        $this->notificationService->createForUsers(
            $this->uniqueUsers($recipients),
            'ticket_comment',
            'New ticket comment',
            "A new comment was added to ticket #{$ticketRecord->id}.",
            ['ticket_id' => $ticketRecord->id]
        );

        $comment->load('user:id,fullName');

        return response()->json([
            'message' => 'Comment added successfully',
            'comment' => [
                'id' => $comment->id,
                'commentText' => $comment->commentText,
                'user' => [
                    'id' => $comment->user?->id,
                    'fullName' => $comment->user?->fullName,
                ],
                'createdAt' => $comment->createdAt,
            ],
        ], 201);
    }

    protected function canViewTicket($user, Ticket $ticket): bool
    {
        return match ($user->role->roleName) {
            'Admin' => true,
            'Manager' => true,
            'IT Support' => $ticket->assignedTo === $user->id,
            default => $ticket->createdBy === $user->id,
        };
    }

    protected function canCommentOnTicket($user, Ticket $ticket): bool
    {
        return match ($user->role->roleName) {
            'Admin' => true,
            'IT Support' => $ticket->assignedTo === $user->id,
            'Employee' => $ticket->createdBy === $user->id,
            default => false,
        };
    }

    protected function uniqueUsers(array $users): array
    {
        $seen = [];
        $result = [];

        foreach ($users as $user) {
            if (!$user instanceof \App\Models\User) {
                continue;
            }

            if (isset($seen[$user->id])) {
                continue;
            }

            $seen[$user->id] = true;
            $result[] = $user;
        }

        return $result;
    }
}
