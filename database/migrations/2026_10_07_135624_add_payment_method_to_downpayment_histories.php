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
        Schema::table('downpayment_histories', function (Blueprint $table) {
            $table->string('payment_method')->default('Office Payment')->after('trn');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('downpayment_histories', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};
