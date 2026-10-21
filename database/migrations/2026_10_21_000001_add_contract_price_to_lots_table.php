<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lots')) {
            Schema::table('lots', function (Blueprint $table) {
                if (! Schema::hasColumn('lots', 'contract_price')) {
                    $table->decimal('contract_price', 12, 2)->nullable()->after('tax_dec_no');
                }
            });

            // Backfill contract_price from financing_records if available
            if (Schema::hasTable('financing_records') && Schema::hasColumn('financing_records', 'contract_price')) {
                $financingRecords = DB::table('financing_records')
                    ->whereNotNull('lot_id')
                    ->whereNotNull('contract_price')
                    ->where('contract_price', '>', 0)
                    ->get();

                foreach ($financingRecords as $fr) {
                    DB::table('lots')
                        ->where('id', $fr->lot_id)
                        ->where(function ($q) {
                            $q->whereNull('contract_price')->orWhere('contract_price', 0);
                        })
                        ->update(['contract_price' => $fr->contract_price]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('lots') && Schema::hasColumn('lots', 'contract_price')) {
            Schema::table('lots', function (Blueprint $table) {
                $table->dropColumn('contract_price');
            });
        }
    }
};
