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
    Schema::table('drivers', function (Blueprint $table) {
        $table->enum('duty_status', ['on_duty', 'off_duty'])->default('off_duty')->after('plate_number');
        $table->decimal('current_latitude', 10, 7)->nullable();
        $table->decimal('current_longitude', 10, 7)->nullable();
        $table->timestamp('last_seen_at')->nullable();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            //
        });
    }
};
