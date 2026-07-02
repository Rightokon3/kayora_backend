<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('size');
    $table->string('tagline');
    $table->integer('price');
    $table->text('heroDesc');
    $table->string('aboutTitle');
    $table->text('aboutBody');
    $table->string('bestUsedTitle');
    $table->json('usedFor');       // Stores nested array strings
    $table->json('specs');         // Stores key-value parameters
    $table->json('regulatory');    // Stores registration numbers
    $table->string('imageColor')->default('#1E5FAF');
    $table->string('orderTitle');
    $table->text('orderDesc');
    $table->boolean('is_popular')->default(false);
    $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};