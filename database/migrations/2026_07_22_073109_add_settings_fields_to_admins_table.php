<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->string('profile_picture')->nullable()->unique()->after('email');
            $table->string('username')->nullable()->unique()->after('employee_id');
            $table->string('phone')->nullable()->after('profile_picture');
            $table->boolean('notify_system')->default(true)->after('status');
            $table->boolean('notify_new_orders')->default(true)->after('notify_system');
            $table->boolean('notify_driver_alerts')->default(true)->after('notify_new_orders');
            $table->boolean('notify_customer_reports')->default(false)->after('notify_driver_alerts');
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn([
                'username', 'phone',
                'notify_system', 'notify_new_orders', 'notify_driver_alerts', 'notify_customer_reports',
            ]);
        });
    }
};