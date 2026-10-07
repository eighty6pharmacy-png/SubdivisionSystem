<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financing_records', function (Blueprint $table) {
            // true = Retention/Conversion is still held by Pag-IBIG/bank (deducted from net proceeds)
            $table->boolean('retention_on_hold')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('financing_records', function (Blueprint $table) {
            $table->dropColumn('retention_on_hold');
        });
    }
};
