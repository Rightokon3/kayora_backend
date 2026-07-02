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
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('address_id');
        $table->string('payment_method');
        $table->string('delivery_timing');
        $table->dateTime('delivery_date_time');
        $table->json('cart_items'); // Stores IDs and quantities
        $table->integer('subtotal');
        $table->integer('delivery_fee');
        $table->integer('service_fee');
        $table->integer('total');
        $table->string('status')->default('pending'); // pending, processing, delivered, cancelled
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
