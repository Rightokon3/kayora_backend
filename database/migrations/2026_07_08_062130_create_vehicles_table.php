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
    Schema::create('vehicles', function (Blueprint $table) {
        $table->id();
        $table->string('brand');
        $table->string('model');
        $table->string('vehicle_type'); // Van, Truck, Mini Truck, Pickup, Motorcycle
        $table->string('plate_number')->unique();
        $table->string('engine_number')->nullable();
        $table->string('chassis_number')->nullable();
        $table->string('color')->nullable();
        $table->string('image_path')->nullable();
        $table->string('registration_image_path')->nullable();
        $table->enum('status', ['Available', 'Assigned', 'Maintenance'])->default('Available');
        $table->foreignId('assigned_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
