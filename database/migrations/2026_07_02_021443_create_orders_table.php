<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('rider_id')->nullable()->constrained()->onDelete('set null');
            
            $table->enum('delivery_timing', ['asap', 'scheduled']);
            $table->dateTime('delivery_date_time');
            $table->enum('status', ['Pending', 'Preparing', 'Active', 'Out for Delivery', 'Completed', 'Cancelled'])->default('Pending');
            $table->enum('payment_method', ['cash', 'card']);
            
            $table->integer('subtotal_kobo');
            $table->integer('delivery_fee_kobo');
            $table->integer('discount_kobo')->default(0);
            $table->integer('total_kobo');
            
            $table->string('address_label');
            $table->text('delivery_address');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            
            $table->dateTime('delivery_completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};