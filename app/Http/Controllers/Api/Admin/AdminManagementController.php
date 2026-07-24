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
     * The `admins` table stores role/status as lowercase DB-safe enum
     * values (role: super_admin|admin — status: active|inactive — see
     * the migration / SQL dump). The frontend (manage-admins.tsx) works
     * in Title Case display labels for badges/dropdowns. These maps are
     * the single source of truth for translating between the two, so
     * every method below stays consistent.
     *
     * NOTE: the DB enum has ONLY these two roles and two statuses. There
     * is no "Department Manager" role or "Suspended" status at the
     * schema level. If you want those later, the enum needs a migration
     * first (ALTER TABLE admins MODIFY ...), then extend these maps and
     * the frontend's Role/Status types together.
     */
    private const ROLE_DB_TO_DISPLAY = [
        'super_admin' => 'Super Administrator',
        'admin' => 'Administrator',
    ];

    private const ROLE_DISPLAY_TO_DB = [
        'Super Administrator' => 'super_admin',
        'Administrator' => 'admin',
    ];

    private const STATUS_DB_TO_DISPLAY = [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ];

    private const STATUS_DISPLAY_TO_DB = [
        'Active' => 'active',
        'Inactive' => 'inactive',
    ];

    /**
     * Prefix used for generated employee_id values, keyed by DB role —
     * matches the existing seeded rows (SUP-0001, ADM-0001 in the SQL
     * dump). If you ever add more roles, add their prefix here too.
     */
    private const EMPLOYEE_ID_PREFIX = [
        'super_admin' => 'SUP',
        'admin' => 'ADM',
    ];

    /**
     * Every method here is Super-Administrator-only. The frontend's
     * AccessDeniedView already blocks the page for non-super-admins,
     * but that's a UI convenience only — this is the actual
     * enforcement, since a request can always be sent directly.
     *
     * FIXED: this used to compare against the display string
     * 'Super Administrator', but $request->user()->role holds the raw
     * DB value 'super_admin' — the comparison never matched, so every
     * request here 403'd for the real super admin.
     */
    private function ensureSuperAdmin(Request $request): void
    {
        abort_unless($request->user()->role === 'super_admin', 403,
            'Only the Super Administrator can manage administrators.');
    }

    /**
     * Generates the next employee_id for a given DB role, following the
     * existing SUP-0001 / ADM-0001 pattern seen in the admins table.
     *
     * FIXED: previously store() never set employee_id at all, even
     * though the column is NOT NULL with no default in the schema —
     * every "Add Administrator" call from the frontend was failing with
     * a raw SQL error ("Field 'employee_id' doesn't have a default
     * value"). This generates the next sequential number per role
     * prefix instead.
     *
     * Looks at the highest existing number for the given prefix (not
     * just row count), so it keeps working correctly even if an admin
     * in the middle of the sequence was ever deleted.
     */
    private function generateEmployeeId(string $dbRole): string
    {
        $prefix = self::EMPLOYEE_ID_PREFIX[$dbRole] ?? 'ADM';

        $lastEmployeeId = Admin::where('employee_id', 'like', "{$prefix}-%")
            ->orderByRaw('CAST(SUBSTRING_INDEX(employee_id, "-", -1) AS UNSIGNED) DESC')
            ->value('employee_id');

        $nextNumber = 1;
        if ($lastEmployeeId && preg_match('/-(\d+)$/', $lastEmployeeId, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        return sprintf('%s-%04d', $prefix, $nextNumber);
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
            'role' => ['required', Rule::in(array_keys(self::ROLE_DISPLAY_TO_DB))],
            'permissions' => ['nullable', 'array'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $dbRole = self::ROLE_DISPLAY_TO_DB[$data['role']];

        // Only an existing Super Administrator can create another one —
        // mirrors canAssignSuperAdmin on the frontend, enforced here too.
        if ($dbRole === 'super_admin' && $request->user()->role !== 'super_admin') {
            abort(403, 'Only a Super Administrator can assign another Super Administrator.');
        }

        $admin = Admin::create([
            'first_name' => $data['firstName'],
            'last_name' => $data['lastName'],
            'username' => $data['username'],
            // `name` is NOT NULL in the schema with no default — the
            // rest of the app (transformProfile in AdminSettingsController,
            // login screens, etc.) reads this column, not first/last
            // separately, so keep it in sync on create.
            'name' => trim($data['firstName'].' '.$data['lastName']),
            // FIXED: employee_id is NOT NULL with no default at the DB
            // level (see SUP-0001 / ADM-0001 in the existing rows) — this
            // was missing entirely before, causing store() to fail with a
            // raw SQL "doesn't have a default value" error on every
            // attempt to add an administrator.
            'employee_id' => $this->generateEmployeeId($dbRole),
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'avatar_url' => $data['avatarUrl'] ?? null,
            // FIXED: profile_picture is also NOT NULL with no default
            // (existing rows use '' rather than NULL) — same failure
            // mode as employee_id above if left unset.
            'profile_picture' => '',
            'department' => $data['department'],
            'role' => $dbRole,
            'status' => 'active',
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
            'role' => ['required', Rule::in(array_keys(self::ROLE_DISPLAY_TO_DB))],
            'status' => ['required', Rule::in(array_keys(self::STATUS_DISPLAY_TO_DB))],
            'permissions' => ['nullable', 'array'],
        ]);

        $dbRole = self::ROLE_DISPLAY_TO_DB[$data['role']];
        $dbStatus = self::STATUS_DISPLAY_TO_DB[$data['status']];

        if ($dbRole === 'super_admin' && $request->user()->role !== 'super_admin') {
            abort(403, 'Only a Super Administrator can assign another Super Administrator.');
        }

        // Don't allow demoting/deactivating the last remaining super
        // admin out of the role via edit either — mirrors the same
        // guard destroy() already has for deletion.
        if ($admin->role === 'super_admin'
            && ($dbRole !== 'super_admin' || $dbStatus !== 'active')
            && Admin::where('role', 'super_admin')->where('status', 'active')->count() <= 1) {
            abort(422, 'At least one active Super Administrator must remain.');
        }

        $admin->update([
            'first_name' => $data['firstName'],
            'last_name' => $data['lastName'],
            'username' => $data['username'],
            'name' => trim($data['firstName'].' '.$data['lastName']),
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'avatar_url' => $data['avatarUrl'] ?? null,
            'department' => $data['department'],
            'role' => $dbRole,
            'status' => $dbStatus,
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

        if ($admin->role === 'super_admin'
            && Admin::where('role', 'super_admin')->count() <= 1) {
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
    
    public function checkAvailability(Request $request)
{
    $this->ensureSuperAdmin($request);

    $data = $request->validate([
        'username' => ['nullable', 'string'],
        'email' => ['nullable', 'string'],
        'excludeId' => ['nullable', 'integer'],
    ]);

    $usernameTaken = false;
    $emailTaken = false;

    if (! empty($data['username'])) {
        $usernameTaken = Admin::where('username', $data['username'])
            ->when(! empty($data['excludeId']), fn ($q) => $q->where('id', '!=', $data['excludeId']))
            ->exists();
    }

    if (! empty($data['email'])) {
        $emailTaken = Admin::where('email', $data['email'])
            ->when(! empty($data['excludeId']), fn ($q) => $q->where('id', '!=', $data['excludeId']))
            ->exists();
    }

    return response()->json([
        'usernameTaken' => $usernameTaken,
        'emailTaken' => $emailTaken,
    ]);
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
            'role' => self::ROLE_DB_TO_DISPLAY[$admin->role] ?? $admin->role,
            'status' => self::STATUS_DB_TO_DISPLAY[$admin->status] ?? $admin->status,
            'lastLogin' => optional($admin->last_login_at)->diffForHumans() ?? 'Never',
            'dateCreated' => optional($admin->created_at)->format('M j, Y') ?? '',
            'permissions' => $admin->permissions ?? [],
        ];
    }
}
