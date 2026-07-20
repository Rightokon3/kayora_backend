<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {

            $table->string('image_url')->nullable()->after('imageColor');
            $table->boolean('available')->default(true)->after('is_popular');
            $table->enum('status', ['Active', 'Out of Stock', 'Draft'])->default('Active')->after('available');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['image_url', 'available', 'status']);
        });
    }
};