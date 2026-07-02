<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('riders', function (Blueprint $table) {
            $table->id();
    $table->string('full_name');
    $table->string('phone')->unique();
    $table->string('vehicle_type')->default('Motorcycle');
    $table->string('motorcycle_reg_number')->nullable();
    $table->decimal('current_latitude', 10, 8)->nullable();
    $table->decimal('current_longitude', 11, 8)->nullable();
    $table->boolean('is_available')->default(true);
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riders');
    }
};
