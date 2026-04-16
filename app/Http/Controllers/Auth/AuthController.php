<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)
                    ->with(['roles.permissions', 'company', 'branch'])
                    ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Your account is deactivated.'], 403);
        }

        // Revoke previous tokens (single session)
        $user->tokens()->delete();

        $token = $user->createToken('api-token')->plainTextToken;

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        ActivityLog::record('login', 'User logged in');

        return response()->json([
            'token'       => $token,
            'user'        => $user,
            'permissions' => $user->allPermissions(),
        ]);
    }

    public function loginWithPin(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id' => 'required|integer',
            'pin'       => 'required|string|min:4|max:6',
        ]);

        $user = User::where('branch_id', $request->branch_id)
                    ->whereNotNull('pin')
                    ->get()
                    ->first(fn ($u) => Hash::check($request->pin, $u->pin));

        if (! $user || ! $user->is_active) {
            return response()->json(['message' => 'Invalid PIN.'], 401);
        }

        $user->tokens()->delete();
        $token = $user->createToken('pin-token', ['*'], now()->addHours(8))->plainTextToken;

        return response()->json([
            'token'       => $token,
            'user'        => $user->only('id', 'name', 'avatar'),
            'permissions' => $user->allPermissions(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        ActivityLog::record('logout', 'User logged out');
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user()->load(['company', 'branch', 'roles.permissions']));
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed',
        ]);

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages(['current_password' => ['Incorrect current password.']]);
        }

        $user->update(['password' => Hash::make($request->password)]);
        $user->tokens()->delete();

        return response()->json(['message' => 'Password changed. Please log in again.']);
    }
}
