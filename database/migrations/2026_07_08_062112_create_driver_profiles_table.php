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
    Schema::create('driver_profiles', function (Blueprint $table) {
        $table->id();
        $table->foreignId('driver_id')->unique()->constrained('drivers')->cascadeOnDelete();

        // Personal Information
        $table->string('middle_name')->nullable();
        $table->enum('gender', ['Male', 'Female'])->nullable();
        $table->date('date_of_birth')->nullable();
        $table->enum('marital_status', ['Single', 'Married', 'Divorced', 'Widowed'])->nullable();
        $table->string('alternative_phone')->nullable();
        $table->string('home_address')->nullable();
        $table->string('city')->nullable();
        $table->string('state')->nullable();
        $table->string('emergency_contact_name')->nullable();
        $table->string('emergency_contact_phone')->nullable();
        $table->string('profile_image')->nullable();

        // Work Information
        $table->string('employee_id')->nullable();
        $table->string('blood_group')->nullable();
        $table->string('genotype')->nullable();
        $table->string('national_id_number')->nullable();
        $table->date('employment_date')->nullable();
        $table->string('department')->nullable();
        $table->string('branch')->nullable();
        $table->string('supervisor')->nullable();

        // Road / License Information
        $table->string('license_number')->nullable();
        $table->date('license_expiry')->nullable();
        $table->string('license_front_image')->nullable();
        $table->string('license_back_image')->nullable();
        $table->string('national_id_image')->nullable();
        $table->unsignedInteger('years_of_experience')->nullable();
        $table->string('previous_employer')->nullable();
        $table->text('additional_notes')->nullable();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_profiles');
    }
};
