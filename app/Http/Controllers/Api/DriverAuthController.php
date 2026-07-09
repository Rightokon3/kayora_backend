<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DriverAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
            'password' => 'required|string',
        ]);

        $driver = Driver::where('driver_id', $request->identifier)
            ->orWhere('email', $request->identifier)
            ->first();

        if (!$driver || !Hash::check($request->password, $driver->password)) {
            return response()->json(['message' => 'Incorrect Driver ID or Password'], 401);
        }

        // Token expires in exactly 30 days — this is what makes "stay logged in
        // for 30 days" actually true, not just "until the app is closed".
        $token = $driver->createToken('driver-app', ['*'], now()->addDays(30))->plainTextToken;

        return response()->json([
            'token' => $token,
            'expiresAt' => now()->addDays(30)->toIso8601String(),
            'driver' => [
                'id' => $driver->id,
                'driverId' => $driver->driver_id,
                'name' => $driver->name,
                'email' => $driver->email,
            ],
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }
}
