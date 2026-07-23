<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
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
            ->orderBy('tickethistory.changedAt', 'asc')
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
            $allowedFields = ['status', 'assignedTo', 'priority'];
            $input = $request->only($allowedFields);
            $validator = Validator::make($input, [
                'status' => [Rule::in(['Open', 'Assigned', 'In Progress', 'Resolved', 'Closed'])],
                'assignedTo' => ['nullable', 'integer', Rule::exists('users', 'id')],
                'priority' => [Rule::in(['Low', 'Medium', 'High', 'Critical'])],
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $this->applyTicketUpdates($ticket, $input, $user, $request->comment ?? null);
            $ticket->save();
        } elseif ($user->role->roleName === 'Admin') {
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
        } else {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $ticket->load(['category', 'creator', 'assignedUser']);

        return response()->json(['ticket' => $ticket]);
    }

    public function destroy($id)
    {
        $user = auth('api')->user();

        if ($user->role->roleName !== 'Admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $ticket = Ticket::find($id);

        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found'], 404);
        }

        $ticket->delete();

        return response()->json(['message' => 'Ticket deleted successfully']);
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

    protected function applyTicketUpdates(Ticket $ticket, array $input, $user, ?string $comment)
    {
        if (isset($input['status']) && $input['status'] !== $ticket->status) {
            DB::table('tickethistory')->insert([
                'ticketId' => $ticket->id,
                'changedBy' => $user->id,
                'oldStatus' => $ticket->status,
                'newStatus' => $input['status'],
                'comment' => $comment,
                'changedAt' => now(),
            ]);
        }

        foreach ($input as $field => $value) {
            if ($field === 'closedAt' && $value === null) {
                continue;
            }
            $ticket->{$field} = $value;
        }
    }
}
