<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ActivityLogController extends Controller
{
    public function index()
    {
        $user = auth('api')->user();

        $query = DB::table('activitylogs')
            ->join('users', 'activitylogs.userId', '=', 'users.id')
            ->select(
                'activitylogs.id',
                'activitylogs.action',
                'activitylogs.description',
                'activitylogs.ipAddress',
                'activitylogs.createdAt',
                'users.id as userId',
                'users.fullName',
                'users.email'
            );

        if ($user->role->roleName === 'Admin') {
            // Admin can see all activity logs.
        } elseif ($user->role->roleName === 'IT Support') {
            $ticketIds = Ticket::where('assignedTo', $user->id)->pluck('id');

            if ($ticketIds->isEmpty()) {
                return response()->json(['logs' => []]);
            }

            $query->where(function ($subQuery) use ($ticketIds) {
                foreach ($ticketIds as $ticketId) {
                    $subQuery->orWhere('activitylogs.description', 'like', '%' . "Ticket #{$ticketId}" . '%');
                }
            });
        } else {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $logs = $query
            ->orderBy('activitylogs.createdAt', 'desc')
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'user' => [
                        'id' => $log->userId,
                        'fullName' => $log->fullName,
                        'email' => $log->email,
                    ],
                    'action' => $log->action,
                    'description' => $log->description,
                    'ipAddress' => $log->ipAddress,
                    'createdAt' => $log->createdAt ? Carbon::parse($log->createdAt)->toIso8601String() : null,
                ];
            })
            ->values();

        return response()->json(['logs' => $logs]);
    }
}
