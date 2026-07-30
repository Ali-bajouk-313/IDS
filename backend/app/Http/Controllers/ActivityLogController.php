<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class ActivityLogController extends Controller
{
    public function index()
    {
        $user = auth('api')->user();

        if ($user->role->roleName !== 'Admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $logs = DB::table('activitylogs')
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
            )
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
                    'createdAt' => $log->createdAt,
                ];
            })
            ->values();

        return response()->json(['logs' => $logs]);
    }
}
