<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
            'password' => 'required|string',
            'remember' => 'nullable|boolean',
        ]);

        $identifier = trim($request->input('identifier'));
        $remember = (bool) $request->boolean('remember');

        // Matches login.tsx's single "Employee ID or Email Address" field —
        // try either column rather than forcing the frontend to know which
        // one the person typed.
        $admin = Admin::where('employee_id', $identifier)
            ->orWhere('email', $identifier)
            ->first();

        if (! $admin || ! Hash::check($request->input('password'), $admin->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid administrator credentials.',
            ], 401);
        }

        if ($admin->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'This admin account has been deactivated.',
            ], 403);
        }

        // 30 days when "Remember Me" is checked, 1 day otherwise — mirrors
        // the driver app's session pattern (services/api.ts on the driver
        // side stores token + expiresAt the same way). Sanctum checks
        // expires_at per-token regardless of global config, so this alone
        // is enough to enforce the cutoff server-side.
        $expiresAt = $remember ? now()->addDays(30) : now()->addDay();

        $token = $admin->createToken('admin-panel', ['*'], $expiresAt)->plainTextToken;

        $admin->update(['last_login_at' => now()]);

        return response()->json([
            'success' => true,
            'token' => $token,
            'expiresAt' => $expiresAt->toIso8601String(),
            'admin' => [
                'id' => $admin->id,
                'employeeId' => $admin->employee_id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => $admin->role,
                'profilePicture' => $admin->profile_picture,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['success' => true]);
    }

    public function me(Request $request)
    {
        $admin = $request->user();

        return response()->json([
            'id' => $admin->id,
            'employeeId' => $admin->employee_id,
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => $admin->role,
            'profilePicture' => $admin->profile_picture,
        ]);
    }
}