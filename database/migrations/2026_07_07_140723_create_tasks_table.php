<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::dropIfExists('tasks'); // Clear out any old versions cleanly
        
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('task_id')->unique(); // e.g., "TSK-2026-001"
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade'); // Foreign key linking to drivers
            $table->string('customer_name');
            $table->string('address');
            $table->string('status')->default('pending'); // 'pending' or 'completed'
            $table->integer('items_count')->default(1);
            $table->date('scheduled_date'); // The delivery date assigned by admin
            $table->decimal('current_latitude', 10, 8)->nullable();
            $table->decimal('current_longitude', 11, 8)->nullable();
            $table->decimal('distance_completed_km', 8, 2)->default(0.00); // Running distance tally updated by tracking delta coordinates
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('tasks');
    }
};