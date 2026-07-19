<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revenue_entries', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date');
            $table->decimal('amount', 12, 2);
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            // One admin can still log multiple entries per day (e.g. a
            // correction), but this speeds up the monthly-sum query the
            // dashboard chart runs on every load.
            $table->index('entry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revenue_entries');
    }
};