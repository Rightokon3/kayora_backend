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
    $table->json('permissions')->nullable()->after('status');
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