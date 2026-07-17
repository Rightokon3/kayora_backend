<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
class UserSettingsController extends Controller
{
    public function getSettings()
    {
        $user = Auth::user();
        return response()->json([
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'profile_picture' => $user->profile_picture,
                'username' => $user->username ?? $user->name,
            ],
            'settings' => [
                'order_notifications' => (bool)$user->order_notifications,
                'new_products' => (bool)$user->new_products_notifications,
            ]
        ]);
    }

 public function updateProfile(Request $request)
    {
        $user = Auth::user();

        // 1. Check if Email is already present for another user
        if ($request->has('email') && $request->input('email') !== $user->email) {
            $emailExists = User::where('email', $request->input('email'))->exists();
            if ($emailExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'The email address is already used.'
                ], 422);
            }
        }

        // 2. Check if Phone Line is already present for another user
        if ($request->has('phone') && !empty($request->input('phone')) && $request->input('phone') !== $user->phone) {
            $phoneExists = User::where('phone', $request->input('phone'))->exists();
            if ($phoneExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'The phone number is already used.'
                ], 422);
            }
        }

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        // 3. Handle Cloudinary base64/file upload if modified
        $imageUrl = $user->profile_picture;
        try {
            if ($request->has('profile_picture_base64') && !empty($request->input('profile_picture_base64'))) {
                $base64Image = $request->input('profile_picture_base64');
                if (!preg_match('/^data:image\/[a-zA-Z]+;base64,/', $base64Image)) {
                    $base64Image = 'data:image/jpeg;base64,' . $base64Image;
                }
                $upload = Cloudinary::uploadApi()->upload($base64Image, [
                    'folder' => 'kayora/profile_pictures',
                ]);
                $imageUrl = $upload['secure_url'];
            } elseif ($request->hasFile('profile_picture')) {
                $upload = Cloudinary::uploadApi()->upload($request->file('profile_picture')->getRealPath(), [
                    'folder' => 'kayora/profile_pictures',
                ]);
                $imageUrl = $upload['secure_url'];
            } elseif ($request->input('remove_picture') == true || $request->input('remove_picture') == 1) {
                $imageUrl = null;
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'We could not update your profile picture. Please try again.',
            ], 502);
        }

        $user->update([
            'name' => $request->input('name'),
            'email' => $request->input('email', $user->email),
            'phone' => $request->input('phone', $user->phone),
            'profile_picture' => $imageUrl,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully!',
            'user' => $user
        ]);
    }

    public function togglePreference(Request $request)
    {
        $request->validate([
            'key' => 'required|in:order_notifications,new_products',
            'value' => 'required|in:0,1'
        ]);

        $user = Auth::user();
        $field = $request->key === 'order_notifications' ? 'order_notifications' : 'new_products_notifications';
        
        $user->update([$field => $request->value]);

        return response()->json(['success' => true]);
    }
    public function requestInactivation(Request $request)
    {
        $request->validate([
            'reason' => 'required|string|max:1000'
        ]);

        // Insert into database safely
        \Illuminate\Support\Facades\DB::table('account_inactivation_requests')->insert([
            'user_id' => Auth::id(),
            'reason' => $request->input('reason'),
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Returns JSON data to match your frontend requirement
        return response()->json([
            'success' => true,
            'message' => 'We have received your account deletion request.'
        ]);
    }
}