<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }


        $userRole = $user->role->roleName;


        if (!in_array($userRole, $roles)) {

            return response()->json([
                'message' => 'Access denied'
            ], 403);

        }


        return $next($request);
    }
}