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
        Schema::table('buyer_master_lists', function (Blueprint $table) {
            $table->string('financing_method')->nullable()->after('pagibig_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('buyer_master_lists', function (Blueprint $table) {
            $table->dropColumn('financing_method');
        });
    }
};
