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
    Schema::create('driver_daily_stats', function (Blueprint $table) {
        $table->id();
        $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
        $table->date('date');
        $table->decimal('distance_km', 8, 2)->default(0);
        $table->decimal('last_latitude', 10, 7)->nullable();
        $table->decimal('last_longitude', 10, 7)->nullable();
        $table->timestamps();

        $table->unique(['driver_id', 'date']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_daily_stats');
    }
};
