<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSettingsController extends Controller
{
    public function getProfile(Request $request)
    {
        return response()->json($this->transformProfile($request->user()));
    }

    /**
     * PUT /api/admin/settings/profile
     * Requires the admin's current password in the same request — this is
     * the "confirm it's truly them" re-auth step. Wrong password returns a
     * 422 with a clear message; nothing is changed.
     */
    public function updateProfile(Request $request)
    {
        $admin = $request->user();

        $validated = $request->validate([
            'username' => 'required|string|max:255|unique:admins,username,' . $admin->id,
            'fullName' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:admins,email,' . $admin->id,
            'phone' => 'nullable|string|max:255',
            'profilePicture' => 'nullable|string',
            'password' => 'required|string',
        ]);

        if (! Hash::check($validated['password'], $admin->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Incorrect password. Your profile was not updated.',
            ], 422);
        }

        $admin->update([
            'username' => $validated['username'],
            'name' => $validated['fullName'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'profile_picture' => $validated['profilePicture'] ?? $admin->profile_picture,
        ]);

        return response()->json($this->transformProfile($admin->fresh()));
    }

    /**
     * PATCH /api/admin/settings/password
     */
    public function updatePassword(Request $request)
    {
        $admin = $request->user();

        $validated = $request->validate([
            'currentPassword' => 'required|string',
            'newPassword' => 'required|string|min:8',
            'confirmPassword' => 'required|string|same:newPassword',
        ]);

        if (! Hash::check($validated['currentPassword'], $admin->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        $admin->update(['password' => Hash::make($validated['newPassword'])]);

        return response()->json(['success' => true]);
    }

    /**
     * PATCH /api/admin/settings/username
     */
    public function updateUsername(Request $request)
    {
        $admin = $request->user();

        $validated = $request->validate([
            'newUsername' => 'required|string|max:255|unique:admins,username,' . $admin->id,
            'currentPassword' => 'required|string',
        ]);

        if (! Hash::check($validated['currentPassword'], $admin->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        $admin->update(['username' => $validated['newUsername']]);

        return response()->json($this->transformProfile($admin->fresh()));
    }

    public function getNotificationPreferences(Request $request)
    {
        return response()->json($this->transformNotificationPrefs($request->user()));
    }

    /**
     * PATCH /api/admin/settings/notifications
     * Any subset of the four keys — a single toggle flip sends just that
     * one key, not the whole object.
     */
    public function updateNotificationPreferences(Request $request)
    {
        $admin = $request->user();

        $validated = $request->validate([
            'systemNotifications' => 'sometimes|boolean',
            'newOrderNotifications' => 'sometimes|boolean',
            'driverAlerts' => 'sometimes|boolean',
            'customerReports' => 'sometimes|boolean',
        ]);

        $columnMap = [
            'systemNotifications' => 'notify_system',
            'newOrderNotifications' => 'notify_new_orders',
            'driverAlerts' => 'notify_driver_alerts',
            'customerReports' => 'notify_customer_reports',
        ];

        $update = [];
        foreach ($columnMap as $key => $column) {
            if (array_key_exists($key, $validated)) {
                $update[$column] = $validated[$key];
            }
        }

        $admin->update($update);

        return response()->json($this->transformNotificationPrefs($admin->fresh()));
    }

    /**
     * GET /api/admin/settings/system-info
     * apiStatus is trivially "Online" — this endpoint responding at all
     * IS the check. databaseStatus does a real connection probe.
     */
    public function getSystemInfo(Request $request)
    {
        $databaseStatus = 'Disconnected';
        try {
            DB::connection()->getPdo();
            $databaseStatus = 'Connected';
        } catch (\Exception $e) {
            $databaseStatus = 'Disconnected';
        }

        return response()->json
        ([
            'appVersion' => config('app.version', '1.0.0'),
            'buildNumber' => config('app.build_number', 'dev'),
            'apiStatus' => 'Online',
            'databaseStatus' => $databaseStatus,
            'lastSyncTime' => now()->toIso8601String(),
            'companyName' => config('app.company_name', config('app.name', 'Kayora')),
            'copyrightYear' => (int) now()->format('Y'),
        ]);
    }

    private function transformProfile(Admin $admin): array
    {
        return [
            'profilePicture' => $admin->profile_picture,
            'username' => $admin->username ?? $admin->employee_id,
            'fullName' => $admin->name,
            'email' => $admin->email,
            'phone' => $admin->phone ?? '',
            'role' => $admin->role,
            'createdAt' => optional($admin->created_at)->toIso8601String() ?? '',
        ];
    }

    private function transformNotificationPrefs(Admin $admin): array
    {
        return [
            'systemNotifications' => (bool) $admin->notify_system,
            'newOrderNotifications' => (bool) $admin->notify_new_orders,
            'driverAlerts' => (bool) $admin->notify_driver_alerts,
            'customerReports' => (bool) $admin->notify_customer_reports,
        ];
    }
}