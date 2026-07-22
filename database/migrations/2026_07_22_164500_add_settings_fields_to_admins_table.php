<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Purely additive — doesn't touch whatever columns your admins
     * table already has (email, password, etc. for admin.guard login).
     * If you already store the admin's name as a single `name` column
     * rather than first_name/last_name, adjust this migration and the
     * controller below before running it.
     */
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('username')->nullable()->unique()->after('last_name');
            $table->string('phone')->nullable()->after('username');
            $table->string('avatar_url')->nullable()->after('phone');
            $table->string('department')->nullable()->after('avatar_url');
            $table->enum('role', ['Super Administrator', 'Administrator', 'Department Manager'])
                ->default('Administrator')
                ->after('department');
            $table->enum('status', ['Active', 'Inactive', 'Suspended'])
                ->default('Active')
                ->after('role');
            // Stored as a JSON array of permission keys, e.g.
            // ["view_dashboard","view_orders"] — matches the
            // PERMISSION_GROUPS keys in manage-admins.tsx exactly.
            $table->json('permissions')->nullable()->after('status');
            $table->timestamp('last_login_at')->nullable()->after('permissions');
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn([
                'first_name', 'last_name', 'username', 'phone', 'avatar_url',
                'department', 'role', 'status', 'permissions', 'last_login_at',
            ]);
        });
    }
};