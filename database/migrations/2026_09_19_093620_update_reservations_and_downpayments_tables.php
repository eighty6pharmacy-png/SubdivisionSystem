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
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('notes');
            $table->string('last_name')->nullable()->after('first_name');
        });

        Schema::table('downpayments', function (Blueprint $table) {
            $table->decimal('monthly_amortization', 10, 2)->default(15000)->after('balance');
            $table->integer('months_to_pay')->default(24)->after('monthly_amortization');
            $table->date('contract_date')->nullable()->after('months_to_pay');
        });

        Schema::create('downpayment_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('downpayment_id');
            $table->decimal('amount', 10, 2);
            $table->date('payment_date');
            $table->string('trn');
            $table->string('status')->default('Paid');
            $table->timestamps();

            $table->foreign('downpayment_id')->references('id')->on('downpayments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('downpayment_histories');
        
        Schema::table('downpayments', function (Blueprint $table) {
            $table->dropColumn(['monthly_amortization', 'months_to_pay', 'contract_date']);
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name']);
        });
    }
};
