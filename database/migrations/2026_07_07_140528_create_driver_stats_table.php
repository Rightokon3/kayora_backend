<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('driver_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained()->onDelete('cascade');
            $table->decimal('distance_km', 8, 2)->default(0.00);
            $table->decimal('hours_online', 5, 2)->default(0.00);
            $table->integer('completed_tasks')->default(0);
            $table->integer('efficiency')->default(100);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('driver_stats');
    }
};