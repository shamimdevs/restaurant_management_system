<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use Closure;
use Illuminate\Http\Request;

class ResolveBranch
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->isSuperAdmin()) {
            $branchId = $request->header('X-Branch-Id')
                     ?? $request->query('branch_id');

            if ($branchId) {
                $valid = Branch::where('id', $branchId)
                    ->where('company_id', $user->company_id)
                    ->where('is_active', true)
                    ->exists();

                if ($valid) {
                    app()->instance('active_branch_id', (int) $branchId);
                }
            }
        }

        return $next($request);
    }
}
