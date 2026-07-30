<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\TicketHistoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    public function __construct(
        protected TicketHistoryService $ticketHistoryService,
        protected ActivityLogService $activityLogService
    )
    {
    }

    public function index(Request $request)
    {
        $user = auth('api')->user();

        $query = Ticket::with(['category', 'creator', 'assignedUser']);

        if ($user->role->roleName === 'Admin') {
            // Admin sees all tickets.
        } elseif ($user->role->roleName === 'Manager') {
            $query->whereHas('creator', function ($q) use ($user) {
                $q->where('departmentId', $user->departmentId);
            });
        } elseif ($user->role->roleName === 'IT Support') {
            $query->where('assignedTo', $user->id);
        } else {
            $query->where('createdBy', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('date')) {
            $query->whereDate('createdAt', $request->date);
        }

        $tickets = $query->orderBy('createdAt', 'desc')->get();

        return response()->json(['tickets' => $tickets]);
    }

    public function store(Request $request)
    {
        $user = auth('api')->user();

        if (!in_array($user->role->roleName, ['Admin', 'Employee'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:150',
            'description' => 'required|string',
            'categoryId' => ['required', 'integer', Rule::exists('categories', 'id')],
            'priority' => ['required', Rule::in(['Low', 'Medium', 'High', 'Critical'])],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $nextId = Ticket::max('id');
        $nextId = $nextId ? $nextId + 1 : 1;
        $ticketNumber = sprintf('TICKET-%05d', $nextId);

        $ticket = Ticket::create([
            'ticketNumber' => $ticketNumber,
            'title' => $request->title,
            'description' => $request->description,
            'categoryId' => $request->categoryId,
            'createdBy' => $user->id,
            'priority' => $request->priority,
            'status' => 'Open',
        ]);

        $this->ticketHistoryService->recordCreated($ticket, $user);
        $this->activityLogService->logTicketCreated($user, $ticket, $request->ip());

        $ticket->load(['category', 'creator', 'assignedUser']);

        return response()->json(['ticket' => $ticket], 201);
    }

    public function show($id)
    {
        $user = auth('api')->user();

        $ticket = Ticket::with(['category', 'creator', 'assignedUser'])->find($id);

        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found'], 404);
        }

        if (!$this->canViewTicket($user, $ticket)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $comments = DB::table('ticketcomments')
            ->where('ticketId', $ticket->id)
            ->join('users', 'ticketcomments.userId', '=', 'users.id')
            ->select('ticketcomments.id', 'ticketcomments.commentText', 'ticketcomments.createdAt', 'users.id as userId', 'users.fullName as userName')
            ->orderBy('ticketcomments.createdAt', 'asc')
            ->get();

        $history = DB::table('tickethistory')
            ->where('ticketId', $ticket->id)
            ->join('users', 'tickethistory.changedBy', '=', 'users.id')
            ->select('tickethistory.id', 'tickethistory.oldStatus', 'tickethistory.newStatus', 'tickethistory.comment', 'tickethistory.changedAt', 'users.id as userId', 'users.fullName as userName')
            ->orderBy('tickethistory.changedAt', 'desc')
            ->get();

        return response()->json([
            'ticket' => $ticket,
            'comments' => $comments,
            'history' => $history,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = auth('api')->user();

        $ticket = Ticket::find($id);

        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found'], 404);
        }

        if ($user->role->roleName === 'Employee') {
            if ($ticket->createdBy !== $user->id || $ticket->assignedTo !== null) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
        }

        if ($user->role->roleName === 'IT Support') {
            if ($ticket->assignedTo !== $user->id) {
                return response()->json(['message' => 'Forbidden'], 403);
            }

            $original = [
                'status' => $ticket->status,
                'priority' => $ticket->priority,
                'assignedTo' => $ticket->assignedTo,
                'title' => $ticket->title,
                'description' => $ticket->description,
                'categoryId' => $ticket->categoryId,
            ];

            $input = $request->only(['status', 'priority']);
            $validator = Validator::make($input, [
                'status' => [Rule::in(['Open', 'Assigned', 'In Progress', 'Resolved', 'Closed'])],
                'priority' => [Rule::in(['Low', 'Medium', 'High', 'Critical'])],
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $this->applyTicketUpdates($ticket, $input, $user, $request->comment ?? null);
            $ticket->save();

            $this->recordTicketUpdateEvents($ticket, $user, $original, $request->ip());
        } elseif ($user->role->roleName === 'Admin') {
            $original = [
                'status' => $ticket->status,
                'priority' => $ticket->priority,
                'assignedTo' => $ticket->assignedTo,
                'title' => $ticket->title,
                'description' => $ticket->description,
                'categoryId' => $ticket->categoryId,
            ];

            $input = $request->only(['title', 'description', 'categoryId', 'priority', 'status', 'assignedTo', 'closedAt']);
            $validator = Validator::make($input, [
                'title' => 'sometimes|required|string|max:150',
                'description' => 'sometimes|required|string',
                'categoryId' => ['sometimes', 'required', 'integer', Rule::exists('categories', 'id')],
                'priority' => [Rule::in(['Low', 'Medium', 'High', 'Critical'])],
                'status' => [Rule::in(['Open', 'Assigned', 'In Progress', 'Resolved', 'Closed'])],
                'assignedTo' => ['nullable', 'integer', Rule::exists('users', 'id')],
                'closedAt' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $this->applyTicketUpdates($ticket, $input, $user, $request->comment ?? null);
            $ticket->save();

            $this->recordTicketUpdateEvents($ticket, $user, $original, $request->ip());
        } else {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $ticket->load(['category', 'creator', 'assignedUser']);

        return response()->json(['ticket' => $ticket]);
    }

    public function destroy(Request $request, $id)
    {
        $user = auth('api')->user();

        if ($user->role->roleName !== 'Admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $ticket = Ticket::find($id);

        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found'], 404);
        }

        $deletedTicket = clone $ticket;
        $ticket->delete();

        $this->activityLogService->logTicketDeleted($user, $deletedTicket, $request->ip());

        return response()->json(['message' => 'Ticket deleted successfully']);
    }

    public function updateStatus(Request $request, $id)
    {
        $user = auth('api')->user();
        $ticket = Ticket::find($id);

        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in(['Open', 'Assigned', 'In Progress', 'Resolved', 'Closed'])],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $allowed = $this->canChangeStatus($user, $ticket, $request->status);

        if (!$allowed) {
            return response()->json(['message' => 'You cannot perform this action'], 403);
        }

        $oldStatus = $ticket->status;
        $ticket->status = $request->status;
        $ticket->save();

        if ($oldStatus !== $ticket->status) {
            if ($ticket->status === 'Closed') {
                $this->ticketHistoryService->recordClosed($ticket, $user, $oldStatus);
            } elseif ($oldStatus === 'Closed' && $ticket->status === 'Open') {
                $this->ticketHistoryService->recordReopened($ticket, $user, $oldStatus);
            } else {
                $this->ticketHistoryService->recordStatusChanged($ticket, $user, $oldStatus, $ticket->status);
            }

            $this->activityLogService->logStatusChanged($user, $ticket, $oldStatus, $ticket->status, $request->ip());
        }

        $ticket->load(['category', 'creator', 'assignedUser']);

        return response()->json(['message' => 'Ticket status updated successfully', 'ticket' => $ticket]);
    }

    public function assignTicket(Request $request, $ticket)
    {
        $user = auth('api')->user();

        if ($user->role->roleName !== 'Admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $ticketRecord = Ticket::find($ticket);

        if (!$ticketRecord) {
            return response()->json(['message' => 'Ticket not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'assigned_to' => ['required', 'integer', Rule::exists('users', 'id')],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $assignedUser = User::find($request->assigned_to);

        if (!$assignedUser) {
            return response()->json(['message' => 'Assigned user not found'], 404);
        }

        if ($assignedUser->role->roleName !== 'IT Support') {
            return response()->json(['message' => 'Assigned user must be an IT Support agent'], 422);
        }

        if ($ticketRecord->status === 'Closed') {
            return response()->json(['message' => 'Closed tickets cannot be assigned'], 422);
        }

        if ($ticketRecord->assignedTo !== null) {
            $currentAssignee = User::find($ticketRecord->assignedTo);
            $name = $currentAssignee?->fullName ?? 'another IT Support agent';

            return response()->json([
                'message' => "Ticket is already assigned to {$name}. It must be rejected by the assigned IT Support first.",
            ], 409);
        }

        $oldAssignedTo = $ticketRecord->assignedTo;
        $oldStatus = $ticketRecord->status;
        $ticketRecord->assignedTo = $assignedUser->id;
        $ticketRecord->status = 'Assigned';
        $ticketRecord->save();

        $action = 'ASSIGNED';
        $this->createAssignmentHistory($ticketRecord, $oldAssignedTo, $assignedUser->id, $user->id, $action);

        $this->ticketHistoryService->recordAssigned($ticketRecord, $user, $assignedUser->fullName, $oldStatus);
        $this->activityLogService->logTicketAssigned($user, $ticketRecord, $assignedUser->fullName, $request->ip());

        if ($oldStatus !== $ticketRecord->status) {
            $this->ticketHistoryService->recordStatusChanged($ticketRecord, $user, $oldStatus, $ticketRecord->status);
        }

        $ticketRecord->load(['category', 'creator', 'assignedUser']);

        return response()->json(['message' => 'Ticket assigned successfully', 'ticket' => $ticketRecord]);
    }

    public function unassignTicket(Request $request, $ticket)
    {
        $user = auth('api')->user();

        $ticketRecord = Ticket::find($ticket);

        if (!$ticketRecord) {
            return response()->json(['message' => 'Ticket not found'], 404);
        }

        $isAssignedSupport = $user->role->roleName === 'IT Support' && $ticketRecord->assignedTo === $user->id;

        if (!$isAssignedSupport) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($ticketRecord->assignedTo === null) {
            return response()->json(['message' => 'Ticket is already unassigned'], 422);
        }

        $oldAssignedTo = $ticketRecord->assignedTo;
        $oldStatus = $ticketRecord->status;
        $ticketRecord->assignedTo = null;
        $ticketRecord->status = 'Open';
        $ticketRecord->save();

        $this->createAssignmentHistory($ticketRecord, $oldAssignedTo, null, $user->id, 'UNASSIGNED');
        $this->ticketHistoryService->recordUnassigned($ticketRecord, $user, $oldStatus);
        $this->activityLogService->logTicketUnassigned($user, $ticketRecord, $request->ip());

        if ($oldStatus !== $ticketRecord->status) {
            if ($oldStatus === 'Closed' && $ticketRecord->status === 'Open') {
                $this->ticketHistoryService->recordReopened($ticketRecord, $user, $oldStatus);
            } else {
                $this->ticketHistoryService->recordStatusChanged($ticketRecord, $user, $oldStatus, $ticketRecord->status);
            }
        }

        $ticketRecord->load(['category', 'creator', 'assignedUser']);

        return response()->json(['message' => 'Ticket unassigned successfully', 'ticket' => $ticketRecord]);
    }

    public function myAssigned(Request $request)
    {
        $user = auth('api')->user();

        if ($user->role->roleName !== 'IT Support') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $query = Ticket::with(['category', 'creator', 'assignedUser'])
            ->where('assignedTo', $user->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('date')) {
            $query->whereDate('createdAt', $request->date);
        }

        $tickets = $query->orderBy('createdAt', 'desc')->get();

        return response()->json(['tickets' => $tickets]);
    }

    public function history($ticket)
    {
        $user = auth('api')->user();
        $ticketRecord = Ticket::with('creator')->find($ticket);

        if (!$ticketRecord) {
            return response()->json(['message' => 'Ticket not found'], 404);
        }

        if (!$this->canViewTicket($user, $ticketRecord)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $history = DB::table('tickethistory')
            ->where('tickethistory.ticketId', $ticketRecord->id)
            ->join('users', 'tickethistory.changedBy', '=', 'users.id')
            ->select(
                'tickethistory.id',
                'tickethistory.oldStatus',
                'tickethistory.newStatus',
                'tickethistory.comment',
                'tickethistory.changedAt',
                'users.id as changedById',
                'users.fullName as changedByName'
            )
            ->orderBy('tickethistory.changedAt', 'desc')
            ->get()
            ->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'changedBy' => [
                        'id' => $entry->changedById,
                        'fullName' => $entry->changedByName,
                    ],
                    'oldStatus' => $entry->oldStatus,
                    'newStatus' => $entry->newStatus,
                    'comment' => $entry->comment,
                    'changedAt' => $entry->changedAt,
                ];
            })
            ->values();

        return response()->json(['history' => $history]);
    }

    protected function canViewTicket($user, Ticket $ticket)
    {
        return match ($user->role->roleName) {
            'Admin' => true,
            'Manager' => $ticket->creator->departmentId === $user->departmentId,
            'IT Support' => $ticket->assignedTo === $user->id,
            default => $ticket->createdBy === $user->id,
        };
    }

    protected function canChangeStatus($user, Ticket $ticket, string $newStatus): bool
    {
        $role = $user->role->roleName;
        $allowedTransitions = [
            'Open' => ['Assigned'],
            'Assigned' => ['In Progress'],
            'In Progress' => ['Resolved'],
            'Resolved' => ['Closed'],
        ];

        if (!isset($allowedTransitions[$ticket->status]) || !in_array($newStatus, $allowedTransitions[$ticket->status], true)) {
            return false;
        }

        if ($role === 'Admin') {
            return true;
        }

        if ($role === 'IT Support') {
            return $ticket->assignedTo === $user->id;
        }

        return false;
    }

    protected function applyTicketUpdates(Ticket $ticket, array $input, $user, ?string $comment)
    {
        foreach ($input as $field => $value) {
            if ($field === 'closedAt' && $value === null) {
                continue;
            }
            $ticket->{$field} = $value;
        }
    }

    protected function recordTicketUpdateEvents(Ticket $ticket, User $user, array $original, ?string $ipAddress): void
    {
        $statusChanged = $original['status'] !== $ticket->status;
        $priorityChanged = $original['priority'] !== $ticket->priority;
        $assignmentChanged = $original['assignedTo'] !== $ticket->assignedTo;

        $generalUpdated =
            $original['title'] !== $ticket->title ||
            $original['description'] !== $ticket->description ||
            $original['categoryId'] !== $ticket->categoryId;

        if ($assignmentChanged) {
            if ($original['assignedTo'] === null && $ticket->assignedTo !== null) {
                $newAssignedUser = User::find($ticket->assignedTo);
                $this->ticketHistoryService->recordAssigned($ticket, $user, $newAssignedUser?->fullName ?? 'Unknown user', $original['status']);
                $this->activityLogService->logTicketAssigned($user, $ticket, $newAssignedUser?->fullName ?? 'Unknown user', $ipAddress);
            } elseif ($original['assignedTo'] !== null && $ticket->assignedTo === null) {
                $this->ticketHistoryService->recordUnassigned($ticket, $user, $original['status']);
                $this->activityLogService->logTicketUnassigned($user, $ticket, $ipAddress);
            } elseif ($original['assignedTo'] !== null && $ticket->assignedTo !== null) {
                $oldAssignedUser = User::find($original['assignedTo']);
                $newAssignedUser = User::find($ticket->assignedTo);

                $this->ticketHistoryService->recordReassigned(
                    $ticket,
                    $user,
                    $oldAssignedUser?->fullName ?? 'Unknown user',
                    $newAssignedUser?->fullName ?? 'Unknown user'
                );

                $this->activityLogService->logTicketReassigned(
                    $user,
                    $ticket,
                    $oldAssignedUser?->fullName ?? 'Unknown user',
                    $newAssignedUser?->fullName ?? 'Unknown user',
                    $ipAddress
                );
            }
        }

        if ($statusChanged) {
            if ($ticket->status === 'Closed') {
                $this->ticketHistoryService->recordClosed($ticket, $user, $original['status']);
            } elseif ($original['status'] === 'Closed' && $ticket->status === 'Open') {
                $this->ticketHistoryService->recordReopened($ticket, $user, $original['status']);
            } else {
                $this->ticketHistoryService->recordStatusChanged($ticket, $user, $original['status'], $ticket->status);
            }

            $this->activityLogService->logStatusChanged($user, $ticket, $original['status'], $ticket->status, $ipAddress);
        }

        if ($priorityChanged) {
            $this->ticketHistoryService->recordPriorityChanged($ticket, $user, $original['priority'], $ticket->priority);
            $this->activityLogService->logPriorityChanged($user, $ticket, $original['priority'], $ticket->priority, $ipAddress);
        }

        if ($generalUpdated) {
            $this->ticketHistoryService->recordUpdated($ticket, $user);
            $this->activityLogService->logTicketUpdated($user, $ticket, $ipAddress);
        }
    }

    protected function createAssignmentHistory(Ticket $ticket, ?int $oldAssignedTo, ?int $newAssignedTo, int $assignedBy, string $action)
    {
        TicketAssignment::create([
            'ticket_id' => $ticket->id,
            'old_assigned_to' => $oldAssignedTo,
            'new_assigned_to' => $newAssignedTo,
            'assigned_by' => $assignedBy,
            'action' => $action,
        ]);
    }
}
