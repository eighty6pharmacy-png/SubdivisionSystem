<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('downpayments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('reservation_id');
            $table->decimal('amount', 15, 2); // amount paid so far
            $table->date('due_date')->nullable();
            $table->string('status')->default('Pending');
            $table->decimal('balance', 15, 2);
            $table->decimal('monthly_amortization', 15, 2)->nullable();
            $table->integer('months_to_pay')->nullable();
            $table->date('contract_date')->nullable();
            $table->decimal('penalty_percentage', 5, 2)->default(0);
            $table->string('paymongo_checkout_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('reservation_id');
            $table->foreign('reservation_id')->references('id')->on('reservations')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('downpayments');
    }
};
