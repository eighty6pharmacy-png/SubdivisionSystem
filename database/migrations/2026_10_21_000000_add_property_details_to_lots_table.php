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
                if (! Schema::hasColumn('lots', 'lot_area')) {
                    $table->decimal('lot_area', 10, 2)->nullable()->after('provider_managed');
                }
                if (! Schema::hasColumn('lots', 'floor_area')) {
                    $table->decimal('floor_area', 10, 2)->nullable()->after('lot_area');
                }
                if (! Schema::hasColumn('lots', 'description')) {
                    $table->text('description')->nullable()->after('floor_area');
                }
                if (! Schema::hasColumn('lots', 'tct_no')) {
                    $table->string('tct_no')->nullable()->after('description');
                }
                if (! Schema::hasColumn('lots', 'pid')) {
                    $table->string('pid')->nullable()->after('tct_no');
                }
                if (! Schema::hasColumn('lots', 'tax_dec_no')) {
                    $table->string('tax_dec_no')->nullable()->after('pid');
                }
            });

            // Backfill property details from buyers that were previously linked to a lot
            if (Schema::hasTable('buyers') && Schema::hasColumn('buyers', 'lot_id')) {
                $buyersWithLots = DB::table('buyers')->whereNotNull('lot_id')->get();
                foreach ($buyersWithLots as $b) {
                    $updates = array_filter([
                        'lot_area' => $b->lot_area ?? null,
                        'floor_area' => $b->floor_area ?? null,
                        'description' => $b->description ?? null,
                        'tct_no' => $b->tct_no ?? null,
                        'pid' => $b->pid ?? null,
                        'tax_dec_no' => $b->tax_dec_no ?? null,
                    ], fn ($v) => $v !== null && $v !== '');

                    if (! empty($updates)) {
                        DB::table('lots')->where('id', $b->lot_id)->update($updates);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('lots')) {
            Schema::table('lots', function (Blueprint $table) {
                $cols = array_filter(
                    ['lot_area', 'floor_area', 'description', 'tct_no', 'pid', 'tax_dec_no'],
                    fn ($col) => Schema::hasColumn('lots', $col)
                );
                if (! empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
