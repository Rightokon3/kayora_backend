<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // 1. Check if the user exists first and look at their account status
        $user = User::where('email', $request->email)->first();

        if ($user && $user->status === 'inactive') {
            return response()->json([
                'success' => false,
                'message' => 'The email address associated with this account has been deactivated.'
            ], 403); // 403 Forbidden status code
        }

        // 2. Proceed with standard authentication check
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
            'profile_picture' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120', // 5MB, matches app-side check
        ]);

        $imageUrl = null;

        // 2. Handle Cloudinary Upload securely if file exists
        if ($request->hasFile('profile_picture')) {
            try {
                $upload = Cloudinary::uploadApi()->upload($request->file('profile_picture')->getRealPath(), [
                    'folder' => 'kayora/profile_pictures',
                ]);
                $imageUrl = $upload['secure_url'];
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'We could not upload your profile picture. Please try again.',
                ], 502);
            }
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

    /**
     * Fetch the authenticated user profile details to view before editing
     * GET /api/user/profile
     */
    public function profile()
    {
        $user = Auth::user();

        return response()->json([
            'success' => true,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'profile_picture' => $user->profile_picture,
                'status' => $user->status,
                'order_notifications' => (bool)$user->order_notifications,
                'new_products_notifications' => (bool)$user->new_products_notifications,
            ]
        ]);
    }
}