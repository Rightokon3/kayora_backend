<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('order_notifications')->default(true);
            $table->boolean('new_products_notifications')->default(true);
        });

        // Migration to track administrative deletion requests safely
        Schema::create('account_inactivation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->enum('account_type', ['customer', 'distributor'])->default('customer');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Removed 'phone' from this array since we didn't create it here
            $table->dropColumn(['status', 'order_notifications', 'new_products_notifications']);
        });
        Schema::dropIfExists('account_inactivation_requests');
    }
};