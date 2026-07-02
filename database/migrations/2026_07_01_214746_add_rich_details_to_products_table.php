<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Only add them if they don't already exist to prevent collision errors
            if (!Schema::hasColumn('products', 'heroDesc')) {
                $table->text('heroDesc')->nullable();
                $table->string('aboutTitle')->nullable();
                $table->text('aboutBody')->nullable();
                $table->string('bestUsedTitle')->nullable();
                $table->json('usedFor')->nullable();
                $table->json('specs')->nullable();
                $table->json('regulatory')->nullable();
                $table->string('imageColor')->default('#1E5FAF');
                $table->string('orderTitle')->nullable();
                $table->text('orderDesc')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'heroDesc', 'aboutTitle', 'aboutBody', 'bestUsedTitle', 
                'usedFor', 'specs', 'regulatory', 'imageColor', 'orderTitle', 'orderDesc'
            ]);
        });
    }
};