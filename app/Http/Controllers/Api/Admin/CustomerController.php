<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    /**
     * GET /api/admin/customers?search=
     * Real backend search (not client-side filtering) across
     * name/email/phone. `id` in the response is a derived display id
     * ("CUS-1001") — userId is the real numeric primary key.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $query = User::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->orderByDesc('created_at')->get();

        // One query for every customer's first address, instead of N+1.
        $addressesByUser = DB::table('addresses')
            ->whereIn('user_id', $users->pluck('id'))
            ->orderBy('id')
            ->get()
            ->groupBy('user_id');

        return response()->json(
            $users->map(function ($user) use ($addressesByUser) {
                $address = optional($addressesByUser->get($user->id))->first();

                // addresses.address is one combined string (no separate
                // street/city/state columns) — best-effort comma split.
                $parts = $address ? array_map('trim', explode(',', $address->address)) : [];

                return [
                    'id' => sprintf('CUS-%04d', 1000 + $user->id),
                    'userId' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone ?? '—',
                    'profilePicture' => $user->profile_picture,
                    'address' => [
                        'street' => $parts[0] ?? ($address->address ?? '—'),
                        'city' => $parts[1] ?? '',
                        'state' => $parts[2] ?? '',
                    ],
                    'joinedAt' => optional($user->created_at)->toIso8601String() ?? '',
                ];
            })->values()
        );
    }

    /**
     * DELETE /api/admin/customers/{id}
     * {id} here is the real numeric users.id (userId in the frontend
     * type), NOT the display "CUS-1001" string. Deletes the customer's
     * addresses first since there's no cascade defined on that FK.
     */
    public function destroy(Request $request, $id)
    {
        $user = User::findOrFail($id);

        DB::transaction(function () use ($user) {
            DB::table('addresses')->where('user_id', $user->id)->delete();
            $user->delete();
        });

        return response()->json(['success' => true]);
    }

    /**
     * GET /api/admin/customers/inactivation-requests
     * Returns raw account_inactivation_requests rows — column names are
     * intentionally left as-is (user_id, account_type, etc.) rather than
     * camelCased, since the admin panel wants to show these exact DB
     * column names in the modal.
     */
    public function inactivationRequests()
    {
        $requests = DB::table('account_inactivation_requests')
            ->where('account_type', 'customer')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($requests);
    }

    /**
     * DELETE /api/admin/customers/inactivation-requests/{id}
     * The "Delete" button in the deletion-requests modal fulfills the
     * request: it deletes the actual customer account tied to this
     * request (addresses + user row), then removes the request itself
     * since there's nothing left to act on.
     */
    public function resolveInactivationRequest(Request $request, $id)
    {
        $inactivationRequest = DB::table('account_inactivation_requests')->where('id', $id)->first();
        abort_unless($inactivationRequest, 404);

        DB::transaction(function () use ($inactivationRequest) {
            DB::table('addresses')->where('user_id', $inactivationRequest->user_id)->delete();
            User::where('id', $inactivationRequest->user_id)->delete();
            DB::table('account_inactivation_requests')->where('id', $inactivationRequest->id)->delete();
        });

        return response()->json(['success' => true]);
    }
}