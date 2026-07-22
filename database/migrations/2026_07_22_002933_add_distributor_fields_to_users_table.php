<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * is_distributor flips to true the moment an admin approves this
     * user's distributor_applications row. From then on the user is
     * a distributor, not a plain customer — this is the "user ID
     * placed in the database to prove that person is no longer a
     * normal user" flag you asked for.
     *
     * distributor_application_id just keeps a pointer back to the
     * approved application, in case you want to show "approved on X"
     * details later without a join on status = approved.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_distributor')->default(false)->after('status');
            $table->foreignId('distributor_application_id')
                ->nullable()
                ->after('is_distributor')
                ->constrained('distributor_applications')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('distributor_application_id');
            $table->dropColumn('is_distributor');
        });
    }
};