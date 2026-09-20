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
            $table->string('paymongo_checkout_id')->nullable();
            $table->string('paymongo_checkout_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('utility_bills', function (Blueprint $table) {
            $table->dropColumn('paymongo_checkout_id');
            $table->dropColumn('paymongo_checkout_url');
        });
    }
};
