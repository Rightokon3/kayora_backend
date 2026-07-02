<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User; // <-- THIS WAS MISSING AND CAUSING THE CRASH

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!Auth::attempt([
            'email' => $request->email,
            'password' => $request->password
        ])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password'
            ], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => $user
        ]);
    }

    public function register(Request $request)
    {
        // 1. Validate the incoming data
        $request->validate([
            'username' => 'required|unique:users',
            'email' => 'required|email|unique:users',
            'phone' => 'required',
            'password' => 'required|min:8|confirmed', // expects 'password_confirmation' from frontend
        ]);

        $imageUrl = null;

        // 2. Handle Cloudinary Upload securely if file exists
        if ($request->hasFile('profile_picture')) {
            $upload = cloudinary()->upload(
                $request->file('profile_picture')->getRealPath(),
                [
                    'folder' => 'kayora/profile_pictures'
                ]
            );
            $imageUrl = $upload->getSecurePath();
        }

        // 3. Create the user using the imported User model
        $user = User::create([
            'username' => $request->username,
            'name' => $request->username, // adding name fallback
            'email' => $request->email,
            'phone' => $request->phone,
            'profile_picture' => $imageUrl,
            'password' => bcrypt($request->password),
        ]);

        // 4. Generate Sanctum token
        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => $user
        ], 201);
    }
}