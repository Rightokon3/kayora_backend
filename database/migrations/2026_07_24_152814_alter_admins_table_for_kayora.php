<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {

            if (!Schema::hasColumn('admins', 'first_name')) {
                $table->string('first_name')->nullable()->after('id');
            }

            if (!Schema::hasColumn('admins', 'last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }

            if (!Schema::hasColumn('admins', 'employee_id')) {
                $table->string('employee_id')->unique()->after('last_name');
            }

            if (!Schema::hasColumn('admins', 'username')) {
                $table->string('username')->nullable()->unique()->after('employee_id');
            }

            if (!Schema::hasColumn('admins', 'profile_picture')) {
                $table->string('profile_picture', 225)->default('')->after('email');
            }

            if (!Schema::hasColumn('admins', 'phone')) {
                $table->string('phone')->nullable()->after('profile_picture');
            }

            if (!Schema::hasColumn('admins', 'avatar_url')) {
                $table->string('avatar_url')->nullable()->after('phone');
            }

            if (!Schema::hasColumn('admins', 'department')) {
                $table->string('department')->nullable()->after('avatar_url');
            }

            if (!Schema::hasColumn('admins', 'permissions')) {
                $table->json('permissions')->nullable()->after('status');
            }

            if (!Schema::hasColumn('admins', 'notify_system')) {
                $table->boolean('notify_system')->default(true)->after('permissions');
            }

            if (!Schema::hasColumn('admins', 'notify_new_orders')) {
                $table->boolean('notify_new_orders')->default(true)->after('notify_system');
            }

            if (!Schema::hasColumn('admins', 'notify_driver_alerts')) {
                $table->boolean('notify_driver_alerts')->default(true)->after('notify_new_orders');
            }

            if (!Schema::hasColumn('admins', 'notify_customer_reports')) {
                $table->boolean('notify_customer_reports')->default(false)->after('notify_driver_alerts');
            }

            if (!Schema::hasColumn('admins', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('remember_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {

            $table->dropColumn([
                'first_name',
                'last_name',
                'employee_id',
                'username',
                'profile_picture',
                'phone',
                'avatar_url',
                'department',
                'permissions',
                'notify_system',
                'notify_new_orders',
                'notify_driver_alerts',
                'notify_customer_reports',
                'last_login_at',
            ]);
        });
    }
};