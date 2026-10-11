<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('external_id')->nullable()->after('id');
        });

        DB::statement('CREATE UNIQUE INDEX users_external_id_active_unique ON users (external_id) WHERE external_id IS NOT NULL AND deleted_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS users_external_id_active_unique');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('external_id');
        });
    }
};
