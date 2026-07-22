<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Assumes an `admins` table/model already exists — it's the model
     * referenced by the `admin.guard` middleware in routes/api.php. One
     * admin can have more than one device (phone + tablet, etc.), hence
     * a separate table instead of a single column on `admins`.
     *
     * If your admins table is actually named something else, change the
     * `constrained('admins')` call below to match before running this.
     */
    public function up(): void
    {
        Schema::create('admin_push_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->string('expo_push_token')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_push_tokens');
    }
};