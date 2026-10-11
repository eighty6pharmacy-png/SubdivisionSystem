<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('downpayment_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('downpayment_id');
            $table->decimal('amount', 15, 2);
            $table->dateTime('payment_date');
            $table->string('trn');
            $table->string('payment_method')->default('Office Payment');
            $table->string('paymongo_payment_id')->nullable();
            $table->string('status')->default('Paid');
            $table->timestamps();

            $table->index('downpayment_id');
            $table->foreign('downpayment_id')->references('id')->on('downpayments')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('downpayment_histories');
    }
};
