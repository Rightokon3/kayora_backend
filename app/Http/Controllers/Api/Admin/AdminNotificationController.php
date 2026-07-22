<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminPushToken;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    /**
     * POST /api/admin/push-token
     * Called once from the admin app after Expo push permission is
     * granted (see hooks/useAdminPushNotifications.ts on the frontend).
     * Upserts on the token itself so logging in again on the same
     * device never creates a duplicate row.
     */
    public function storeToken(Request $request)
    {
        $request->validate([
            'expoPushToken' => ['required', 'string'],
        ]);

        AdminPushToken::updateOrCreate(
            ['expo_push_token' => $request->expoPushToken],
            ['admin_id' => $request->user()->id]
        );

        return response()->json(['success' => true]);
    }
}