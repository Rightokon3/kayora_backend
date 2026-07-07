<?php

// database/migrations/xxxx_xx_xx_create_vehicles_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade');
            $table->string('make');
            $table->string('model');
            $table->string('year');
            $table->string('color')->nullable();
            $table->string('plate_number')->unique();
            
            // Document/Image Cloudinary URLs
            $table->string('vehicle_picture')->nullable();
            $table->string('vehicle_license_picture')->nullable();
            $table->string('insurance_document_path')->nullable();
            $table->string('road_worthiness_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};