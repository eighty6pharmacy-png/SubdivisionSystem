<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds guard duty schedule fields (schedule_days, shift_type, shift_start, shift_end)
 * to the guards table.
 */
return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        if (! Schema::hasColumn('guards', 'schedule_days')) {
            Schema::table('guards', function (Blueprint $table) {
                $table->json('schedule_days')->nullable();
                $table->string('shift_type')->nullable()->default('day');
                $table->time('shift_start')->nullable()->default('06:00:00');
                $table->time('shift_end')->nullable()->default('18:00:00');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('guards', 'schedule_days')) {
            Schema::table('guards', function (Blueprint $table) {
                $table->dropColumn(['schedule_days', 'shift_type', 'shift_start', 'shift_end']);
            });
        }
    }
};
