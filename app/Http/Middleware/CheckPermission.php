<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $branchId = $user->branch_id ?? $request->header('X-Branch-Id');

        if (! $user->hasPermission($permission, $branchId ? (int) $branchId : null)) {
            return response()->json([
                'message' => "You do not have permission to perform this action ({$permission}).",
            ], 403);
        }

        return $next($request);
    }
}
