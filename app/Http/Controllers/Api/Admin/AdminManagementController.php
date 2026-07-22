<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminManagementController extends Controller
{
    /**
     * Every method here is Super-Administrator-only. The frontend's
     * AccessDeniedView already blocks the page for non-super-admins,
     * but that's a UI convenience only — this is the actual
     * enforcement, since a request can always be sent directly.
     */
    private function ensureSuperAdmin(Request $request): void
    {
        abort_unless($request->user()->role === 'Super Administrator', 403,
            'Only the Super Administrator can manage administrators.');
    }

    /**
     * GET /api/admin/admins?search=
     * Matches adminService.getAdministrators / searchAdministrators —
     * the frontend used to filter client-side, this does it server-side
     * across name/username/email/department/role instead.
     */
    public function index(Request $request)
    {
        $this->ensureSuperAdmin($request);

        $search = trim((string) $request->query('search', ''));

        $query = Admin::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%")
                    ->orWhere('role', 'like', "%{$search}%");
            });
        }

        return response()->json(
            $query->orderByDesc('created_at')->get()->map(fn ($admin) => $this->present($admin))
        );
    }

    /**
     * POST /api/admin/admins
     * avatarUrl is expected to already be a hosted URL — upload the
     * picked image via the existing POST /admin/upload-image endpoint
     * first (same pattern as everywhere else in the app), then send
     * the returned URL here. This endpoint does not accept raw files.
     */
    public function store(Request $request)
    {
        $this->ensureSuperAdmin($request);

        $data = $request->validate([
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:admins,username'],
            'email' => ['required', 'email', 'max:255', 'unique:admins,email'],
            'phone' => ['nullable', 'string', 'max:255'],
            'avatarUrl' => ['nullable', 'string'],
            'department' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::in(['Super Administrator', 'Administrator', 'Department Manager'])],
            'permissions' => ['nullable', 'array'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        // Only an existing Super Administrator can create another one —
        // mirrors canAssignSuperAdmin on the frontend, enforced here too.
        if ($data['role'] === 'Super Administrator' && $request->user()->role !== 'Super Administrator') {
            abort(403, 'Only a Super Administrator can assign another Super Administrator.');
        }

        $admin = Admin::create([
            'first_name' => $data['firstName'],
            'last_name' => $data['lastName'],
            'username' => $data['username'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'avatar_url' => $data['avatarUrl'] ?? null,
            'department' => $data['department'],
            'role' => $data['role'],
            'status' => 'Active',
            'permissions' => $data['permissions'] ?? [],
            'password' => Hash::make($data['password']),
        ]);

        return response()->json($this->present($admin), 201);
    }

    /**
     * PUT /api/admin/admins/{admin}
     */
    public function update(Request $request, Admin $admin)
    {
        $this->ensureSuperAdmin($request);

        $data = $request->validate([
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('admins', 'username')->ignore($admin->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('admins', 'email')->ignore($admin->id)],
            'phone' => ['nullable', 'string', 'max:255'],
            'avatarUrl' => ['nullable', 'string'],
            'department' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::in(['Super Administrator', 'Administrator', 'Department Manager'])],
            'status' => ['required', Rule::in(['Active', 'Inactive', 'Suspended'])],
            'permissions' => ['nullable', 'array'],
        ]);

        if ($data['role'] === 'Super Administrator' && $request->user()->role !== 'Super Administrator') {
            abort(403, 'Only a Super Administrator can assign another Super Administrator.');
        }

        $admin->update([
            'first_name' => $data['firstName'],
            'last_name' => $data['lastName'],
            'username' => $data['username'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'avatar_url' => $data['avatarUrl'] ?? null,
            'department' => $data['department'],
            'role' => $data['role'],
            'status' => $data['status'],
            'permissions' => $data['permissions'] ?? [],
        ]);

        return response()->json($this->present($admin));
    }

    /**
     * DELETE /api/admin/admins/{admin}
     * The frontend already confirms the acting admin's password via
     * POST /admins/confirm-password immediately before calling this —
     * this endpoint doesn't re-check it, but does refuse self-deletion
     * and refuses to remove the last remaining Super Administrator so
     * the panel can never lock everyone out.
     */
    public function destroy(Request $request, Admin $admin)
    {
        $this->ensureSuperAdmin($request);

        if ($admin->id === $request->user()->id) {
            abort(422, 'You cannot delete your own administrator account.');
        }

        if ($admin->role === 'Super Administrator'
            && Admin::where('role', 'Super Administrator')->count() <= 1) {
            abort(422, 'At least one Super Administrator must remain.');
        }

        $admin->delete();

        return response()->json(['success' => true]);
    }

    /**
     * POST /api/admin/admins/confirm-password
     * Checks the CURRENTLY LOGGED-IN admin's own password — this is
     * "re-enter your password to confirm this destructive action",
     * not a check against the target administrator being deleted.
     */
    public function confirmPassword(Request $request)
    {
        $request->validate(['password' => ['required', 'string']]);

        $valid = Hash::check($request->password, $request->user()->password);

        return response()->json(['valid' => $valid]);
    }

    private function present(Admin $admin): array
    {
        return [
            'id' => (string) $admin->id,
            'firstName' => $admin->first_name,
            'lastName' => $admin->last_name,
            'username' => $admin->username,
            'email' => $admin->email,
            'phone' => $admin->phone ?? '',
            'avatarUrl' => $admin->avatar_url,
            'department' => $admin->department,
            'role' => $admin->role,
            'status' => $admin->status,
            'lastLogin' => optional($admin->last_login_at)->diffForHumans() ?? 'Never',
            'dateCreated' => optional($admin->created_at)->format('M j, Y') ?? '',
            'permissions' => $admin->permissions ?? [],
        ];
    }
}