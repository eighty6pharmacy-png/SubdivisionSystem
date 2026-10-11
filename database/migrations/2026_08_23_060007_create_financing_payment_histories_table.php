<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financing_payment_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('financing_record_id');
            $table->decimal('amount_paid', 15, 2);
            $table->timestamp('payment_date');
            $table->string('trn')->nullable();
            $table->string('payment_method')->nullable()->default('Office Payment');
            $table->string('paymongo_payment_id')->nullable();
            $table->string('status')->default('Paid');
            $table->timestamps();

            $table->index('financing_record_id');
            $table->foreign('financing_record_id')->references('id')->on('financing_records')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financing_payment_histories');
    }
};
