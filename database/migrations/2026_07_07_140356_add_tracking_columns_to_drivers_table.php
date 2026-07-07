
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('drivers', function (Blueprint $table) {
            $table->string('status')->default('Offline')->after('password'); // Available, Busy, Offline
            $table->string('working_hours')->default('7:00 AM – 5:00 PM')->after('status');
            $table->decimal('latitude', 10, 8)->nullable()->after('address');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
        });
    }

    public function down(): void {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn(['status', 'working_hours', 'latitude', 'longitude']);
        });
    }
};
