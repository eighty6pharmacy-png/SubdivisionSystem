<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('visitors', 'exit_time')) {
            Schema::table('visitors', function (Blueprint $table) {
                $table->string('exit_time')->nullable()->after('arrival_time');
            });
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE visitors DROP CONSTRAINT IF EXISTS visitors_status_check');
            DB::statement("ALTER TABLE visitors ADD CONSTRAINT visitors_status_check CHECK (status IN ('Pending', 'Approved', 'Entered', 'Exited', 'Rejected', 'Awaiting Admin Approval'))");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE visitors DROP CONSTRAINT IF EXISTS visitors_status_check');
            DB::statement("ALTER TABLE visitors ADD CONSTRAINT visitors_status_check CHECK (status IN ('Pending', 'Approved', 'Entered', 'Rejected', 'Awaiting Admin Approval'))");
        }

        if (Schema::hasColumn('visitors', 'exit_time')) {
            Schema::table('visitors', function (Blueprint $table) {
                $table->dropColumn('exit_time');
            });
        }
    }
};
