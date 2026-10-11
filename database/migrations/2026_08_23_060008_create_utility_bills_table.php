<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utility_bills', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type'); // 'electricity' or 'water'
            $table->uuid('user_id')->nullable(); // Can be null if lot is vacant
            $table->uuid('lot_id');
            $table->date('billing_period')->nullable(); // first day of the billed month
            $table->integer('previous_reading')->default(0);
            $table->date('previous_reading_date')->nullable();
            $table->integer('current_reading')->default(0);
            $table->date('current_reading_date')->nullable();
            $table->integer('usage_value'); // in kWh or m3
            $table->decimal('amount', 15, 2);
            $table->decimal('previous_balance', 15, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->string('status')->default('unpaid');
            $table->boolean('is_at_risk')->default(false);
            $table->string('paymongo_checkout_id')->nullable();
            $table->string('paymongo_checkout_url')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('lot_id');

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('lot_id')->references('id')->on('lots')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utility_bills');
    }
};
