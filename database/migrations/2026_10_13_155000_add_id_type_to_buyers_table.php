<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Stores the ID type separately from the ID number in proof_of_id.
 * Databases that already ran the column reorder get buyers rebuilt so id_type
 * sits immediately before the ID number. Fresh installs let that later reorder do it.
 */
return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        if (! Schema::hasColumn('buyers', 'id_type')) {
            Schema::table('buyers', function (Blueprint $table) {
                $table->string('id_type')->nullable();
            });
        }

        $reorderName = '2026_10_13_160000_reorder_application_table_columns';
        if (! DB::table('migrations')->where('migration', $reorderName)->exists()) {
            return;
        }

        $reorder = require database_path('migrations/'.$reorderName.'.php');
        $reorder->up();
    }

    public function down(): void
    {
        if (Schema::hasColumn('buyers', 'id_type')) {
            Schema::table('buyers', function (Blueprint $table) {
                $table->dropColumn('id_type');
            });
        }
    }
};
