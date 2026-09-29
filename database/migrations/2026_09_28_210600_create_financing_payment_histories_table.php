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
        Schema::create('financing_payment_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('financing_record_id')->constrained('financing_records')->onDelete('cascade');
            $table->decimal('amount_paid', 10, 2);
            $table->timestamp('payment_date');
            $table->string('trn')->nullable();
            $table->string('status')->default('Paid');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financing_payment_histories');
    }
};
