<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE utility_bills DROP CONSTRAINT IF EXISTS utility_bills_status_check');
        DB::statement("ALTER TABLE utility_bills ADD CONSTRAINT utility_bills_status_check CHECK (status IN ('unpaid', 'partially_paid', 'paid', 'pending', 'overdue'))");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE utility_bills DROP CONSTRAINT IF EXISTS utility_bills_status_check');
        DB::statement("ALTER TABLE utility_bills ADD CONSTRAINT utility_bills_status_check CHECK (status IN ('unpaid', 'paid', 'pending', 'overdue'))");
    }
};
