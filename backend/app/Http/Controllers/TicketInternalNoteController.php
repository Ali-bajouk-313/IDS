<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketInternalNote;
use App\Services\ActivityLogService;
use App\Services\TicketHistoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TicketInternalNoteController extends Controller
{
    public function __construct(
        protected TicketHistoryService $ticketHistoryService,
        protected ActivityLogService $activityLogService
    ) {
    }

    public function index($ticket)
    {
        $user = auth('api')->user();
        $ticketRecord = Ticket::find($ticket);

        if (!$ticketRecord) {
            return response()->json(['message' => 'Ticket not found'], 404);
        }

        if (!$this->canAccessInternalNotes($user->role->roleName, $user->id, $ticketRecord->assignedTo)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $notes = TicketInternalNote::with('user:id,fullName')
            ->where('ticket_id', $ticketRecord->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (TicketInternalNote $note) {
                return [
                    'id' => $note->id,
                    'note' => $note->note,
                    'createdAt' => $note->created_at,
                    'user' => [
                        'id' => $note->user?->id,
                        'fullName' => $note->user?->fullName,
                    ],
                ];
            })
            ->values();

        return response()->json(['notes' => $notes]);
    }

    public function store(Request $request, $ticket)
    {
        $user = auth('api')->user();
        $ticketRecord = Ticket::find($ticket);

        if (!$ticketRecord) {
            return response()->json(['message' => 'Ticket not found'], 404);
        }

        if (!$this->canAccessInternalNotes($user->role->roleName, $user->id, $ticketRecord->assignedTo)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'note' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $internalNote = TicketInternalNote::create([
            'ticket_id' => $ticketRecord->id,
            'user_id' => $user->id,
            'note' => trim($request->note),
        ]);

        $this->ticketHistoryService->recordInternalNoteAdded($ticketRecord, $user);
        $this->activityLogService->logInternalNoteAdded($user, $ticketRecord, $request->ip());

        $internalNote->load('user:id,fullName');

        return response()->json([
            'message' => 'Internal note added successfully',
            'note' => [
                'id' => $internalNote->id,
                'note' => $internalNote->note,
                'createdAt' => $internalNote->created_at,
                'user' => [
                    'id' => $internalNote->user?->id,
                    'fullName' => $internalNote->user?->fullName,
                ],
            ],
        ], 201);
    }

    public function destroy($id)
    {
        $user = auth('api')->user();

        if ($user->role->roleName !== 'Admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $internalNote = TicketInternalNote::find($id);

        if (!$internalNote) {
            return response()->json(['message' => 'Internal note not found'], 404);
        }

        $internalNote->delete();

        return response()->json(['message' => 'Internal note deleted successfully']);
    }

    protected function canAccessInternalNotes(string $role, int $userId, ?int $assignedTo): bool
    {
        if ($role === 'Admin') {
            return true;
        }

        if ($role === 'IT Support') {
            return $assignedTo === $userId;
        }

        return false;
    }
}
