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
        Schema::table('downpayments', function (Blueprint $table) {
            $table->string('paymongo_checkout_id')->nullable();
        });
        Schema::table('financing_records', function (Blueprint $table) {
            $table->string('paymongo_checkout_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('downpayments', function (Blueprint $table) {
            $table->dropColumn('paymongo_checkout_id');
        });
        Schema::table('financing_records', function (Blueprint $table) {
            $table->dropColumn('paymongo_checkout_id');
        });
    }
};
