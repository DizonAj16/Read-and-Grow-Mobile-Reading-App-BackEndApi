<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();
        Log::info('RoleMiddleware triggered', [
            'user_id' => $user?->id,
            'user_role' => $user?->role,
            'required_roles' => $roles,
        ]);
        if (!$user || !in_array($user->role, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have the required role(s).',
            ], 403);
        }

        return $next($request);
    }
}
