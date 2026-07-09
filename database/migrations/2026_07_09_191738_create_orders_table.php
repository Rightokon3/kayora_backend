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
    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->string('order_number')->unique(); // e.g. KYA-88213
        $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();

        $table->string('customer_name');
        $table->string('customer_phone');
        $table->string('customer_email')->nullable();
        $table->string('delivery_address');
        $table->string('nearest_landmark')->nullable();
        $table->decimal('latitude', 10, 7);
        $table->decimal('longitude', 10, 7);

        $table->decimal('amount', 10, 2);
        $table->enum('status', [
            'Pending', 'Accepted', 'Assigned', 'Scheduled',
            'Preparing', 'Out For Delivery', 'Delivered', 'Cancelled',
        ])->default('Pending');

        $table->string('payment_method')->nullable();
        $table->string('payment_status')->nullable();
        $table->string('transaction_id')->nullable();
        $table->string('delivery_type')->default('Instant');
        $table->date('scheduled_date')->nullable();
        $table->string('scheduled_time')->nullable();
        $table->string('priority')->default('Normal');
        $table->text('special_instructions')->nullable();

        $table->decimal('distance_km', 8, 2)->nullable();
        $table->string('eta')->nullable();

        $table->timestamp('assigned_at')->nullable();
        $table->timestamp('started_at')->nullable();
        $table->timestamp('completed_at')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
