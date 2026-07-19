<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            // Only two roles exist in the frontend's ROLE_PERMISSIONS map
            // (login.tsx) — super_admin has every permission, admin has a
            // fixed subset. Keep this enum in lockstep with that file if
            // a third role is ever introduced.
            $table->enum('role', ['super_admin', 'admin'])->default('admin');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->rememberToken();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};