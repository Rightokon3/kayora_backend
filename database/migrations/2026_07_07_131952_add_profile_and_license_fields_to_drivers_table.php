<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->string('phone_number')->nullable()->after('email');
            $table->string('profile_picture')->nullable()->after('phone_number'); // Cloudinary URL
            $table->text('address')->nullable()->after('profile_picture');
            
            $table->string('license_number')->nullable()->after('address');
            $table->date('license_expiry_date')->nullable()->after('license_number');
            $table->string('license_picture')->nullable()->after('license_expiry_date'); // Cloudinary URL
        });
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn(['phone_number', 'profile_picture', 'address', 'license_number', 'license_expiry_date', 'license_picture']);
        });
    }
};