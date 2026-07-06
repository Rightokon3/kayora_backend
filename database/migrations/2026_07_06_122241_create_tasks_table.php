<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::create('tasks', function (Blueprint $table) {
        $table->id();
        $table->string('task_id')->unique();
        $table->foreignId('driver_id')->constrained('users')->onDelete('cascade');
        $table->string('customer_name');
        $table->text('address');
        $table->string('status')->default('pending');
        $table->integer('items_count')->default(1);
        $table->date('scheduled_date');
        $table->decimal('current_latitude', 10, 8)->nullable();
        $table->decimal('current_longitude', 11, 8)->nullable();
        $table->decimal('distance_completed_km', 8, 2)->default(0.00);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
