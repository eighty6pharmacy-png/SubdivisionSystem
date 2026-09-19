<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('utility_bills', function (Blueprint $table) {
            $table->integer('previous_reading')->default(0)->after('lot_id');
            $table->integer('current_reading')->default(0)->after('previous_reading');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('utility_bills', function (Blueprint $table) {
            $table->dropColumn(['previous_reading', 'current_reading']);
        });
    }
};
