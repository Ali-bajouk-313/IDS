<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function admin(Request $request)
    {
        $tickets = Ticket::query()->with(['category', 'creator', 'assignedUser'])->get();
        $users = User::query()->with('role')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $this->buildSummary($tickets),
                'priority' => $this->buildPriorityBreakdown($tickets),
                'category' => $this->buildCategoryBreakdown($tickets),
                'monthlyTickets' => $this->buildMonthlyTickets($tickets),
                'userStats' => $this->buildUserStats($users, $tickets),
                'recentTickets' => $tickets->sortByDesc('createdAt')->take(8)->values()->map(function (Ticket $ticket) {
                    return [
                        'id' => $ticket->id,
                        'ticketNumber' => $ticket->ticketNumber,
                        'title' => $ticket->title,
                        'status' => $ticket->status,
                        'priority' => $ticket->priority,
                        'createdAt' => $ticket->createdAt?->toISOString(),
                        'creator' => $ticket->creator?->fullName,
                    ];
                }),
            ],
        ]);
    }

    public function manager(Request $request)
    {
        $user = $request->user();

        $tickets = Ticket::query()
            ->with(['category', 'creator', 'assignedUser'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $this->buildSummary($tickets),
                'priority' => $this->buildPriorityBreakdown($tickets),
                'category' => $this->buildCategoryBreakdown($tickets),
                'monthlyTickets' => $this->buildMonthlyTickets($tickets),
                'teamPerformance' => $this->buildTeamPerformance($tickets),
                'recentTickets' => $tickets->sortByDesc('createdAt')->take(8)->values()->map(function (Ticket $ticket) {
                    return [
                        'id' => $ticket->id,
                        'ticketNumber' => $ticket->ticketNumber,
                        'title' => $ticket->title,
                        'status' => $ticket->status,
                        'priority' => $ticket->priority,
                        'createdAt' => $ticket->createdAt?->toISOString(),
                        'creator' => $ticket->creator?->fullName,
                        'assignedTo' => $ticket->assignedUser?->fullName,
                    ];
                }),
            ],
        ]);
    }

    public function support(Request $request)
    {
        $user = $request->user();
        $tickets = Ticket::query()
            ->with(['category', 'creator', 'assignedUser'])
            ->where('assignedTo', $user->id)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'totalAssignedTickets' => $tickets->count(),
                    'openAssignedTickets' => $tickets->whereIn('status', ['Open', 'Assigned'])->count(),
                    'inProgressAssignedTickets' => $tickets->where('status', 'In Progress')->count(),
                    'resolvedAssignedTickets' => $tickets->where('status', 'Resolved')->count(),
                    'closedAssignedTickets' => $tickets->where('status', 'Closed')->count(),
                ],
                'priority' => $this->buildPriorityBreakdown($tickets),
                'recentAssignedTickets' => $tickets->sortByDesc('createdAt')->take(8)->values()->map(function (Ticket $ticket) {
                    return [
                        'id' => $ticket->id,
                        'ticketNumber' => $ticket->ticketNumber,
                        'title' => $ticket->title,
                        'status' => $ticket->status,
                        'priority' => $ticket->priority,
                        'createdAt' => $ticket->createdAt?->toISOString(),
                        'creator' => $ticket->creator?->fullName,
                    ];
                }),
                'averageResolutionTime' => $this->averageResolutionTime($tickets),
            ],
        ]);
    }

    public function employee(Request $request)
    {
        $user = $request->user();
        $tickets = Ticket::query()
            ->with(['category', 'creator', 'assignedUser'])
            ->where('createdBy', $user->id)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'totalCreatedTickets' => $tickets->count(),
                    'openTickets' => $tickets->whereIn('status', ['Open', 'Assigned'])->count(),
                    'inProgressTickets' => $tickets->where('status', 'In Progress')->count(),
                    'resolvedTickets' => $tickets->where('status', 'Resolved')->count(),
                    'closedTickets' => $tickets->where('status', 'Closed')->count(),
                ],
                'priority' => $this->buildPriorityBreakdown($tickets),
                'recentTickets' => $tickets->sortByDesc('createdAt')->take(8)->values()->map(function (Ticket $ticket) {
                    return [
                        'id' => $ticket->id,
                        'ticketNumber' => $ticket->ticketNumber,
                        'title' => $ticket->title,
                        'status' => $ticket->status,
                        'priority' => $ticket->priority,
                        'createdAt' => $ticket->createdAt?->toISOString(),
                        'category' => $ticket->category?->categoryName,
                    ];
                }),
            ],
        ]);
    }

    private function buildSummary($tickets): array
    {
        $statusCounts = $tickets->groupBy('status');

        return [
            'totalTickets' => $tickets->count(),
            'openTickets' => $statusCounts->get('Open', collect())->count(),
            'inProgressTickets' => $statusCounts->get('In Progress', collect())->count(),
            'resolvedTickets' => $statusCounts->get('Resolved', collect())->count(),
            'closedTickets' => $statusCounts->get('Closed', collect())->count(),
        ];
    }

    private function buildPriorityBreakdown($tickets): array
    {
        $priorities = ['Low', 'Medium', 'High', 'Urgent'];
        $counts = [];

        foreach ($priorities as $priority) {
            $counts[strtolower($priority)] = $tickets->where('priority', $priority)->count();
        }

        return $counts;
    }

    private function buildCategoryBreakdown($tickets): array
    {
        return $tickets->groupBy(function ($ticket) {
            return $ticket->category?->categoryName ?? 'Uncategorized';
        })->map(function ($group) {
            return $group->count();
        })->sortDesc()->toArray();
    }

    private function buildMonthlyTickets($tickets): array
    {
        $currentYear = now()->year;
        $monthly = collect(range(1, 12))->map(function ($month) use ($tickets, $currentYear) {
            $count = $tickets->filter(function ($ticket) use ($month, $currentYear) {
                return $ticket->createdAt && $ticket->createdAt->year === $currentYear && $ticket->createdAt->month === $month;
            })->count();

            return [
                'month' => now()->month($month)->shortEnglishMonth,
                'count' => $count,
            ];
        });

        return $monthly->values()->all();
    }

    private function buildUserStats($users, $tickets): array
    {
        $activeUsers = $users->where('status', 'Active')->count();
        $usersByRole = $users->groupBy(function ($user) {
            return $user->role?->roleName ?? 'Unknown';
        })->map(function ($group) {
            return $group->count();
        });

        return [
            'totalUsers' => $users->count(),
            'activeUsers' => $activeUsers,
            'usersByRole' => $usersByRole->toArray(),
        ];
    }

    private function buildTeamPerformance($tickets): array
    {
        $assigned = $tickets->whereNotNull('assignedTo')->count();
        $unassigned = $tickets->whereNull('assignedTo')->count();
        $resolved = $tickets->where('status', 'Resolved')->count();

        return [
            'assignedTickets' => $assigned,
            'unassignedTickets' => $unassigned,
            'resolvedTickets' => $resolved,
        ];
    }

    private function averageResolutionTime($tickets): ?float
    {
        $resolvedTickets = $tickets->filter(function (Ticket $ticket) {
            return $ticket->status === 'Resolved' || $ticket->status === 'Closed';
        })->filter(function (Ticket $ticket) {
            return $ticket->createdAt && $ticket->closedAt;
        });

        if ($resolvedTickets->isEmpty()) {
            return 0;
        }

        $hours = $resolvedTickets->sum(function (Ticket $ticket) {
            return $ticket->createdAt->diffInHours($ticket->closedAt);
        });

        return round($hours / $resolvedTickets->count(), 2);
    }
}
