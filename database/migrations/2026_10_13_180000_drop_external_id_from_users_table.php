<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Accounts are matched by email. The spreadsheet external_id is no longer stored.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP INDEX IF EXISTS users_external_id_active_unique');

        if (Schema::hasColumn('users', 'external_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('external_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'external_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('external_id')->nullable();
            });
        }

        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS users_external_id_active_unique ON users (external_id) WHERE external_id IS NOT NULL AND deleted_at IS NULL');
    }
};
