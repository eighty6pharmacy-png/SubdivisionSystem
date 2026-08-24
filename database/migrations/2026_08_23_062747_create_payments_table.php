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
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('utility_bill_id');
            $table->decimal('amount_paid', 10, 2);
            $table->string('method')->nullable();
            $table->string('trn')->unique();
            $table->date('payment_date');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('utility_bill_id')->references('id')->on('utility_bills')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
