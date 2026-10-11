<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable(); // Guest could have null user_id
            $table->uuid('lot_id');
            $table->uuid('buyer_master_list_id')->nullable();
            $table->string('status')->default('Pending'); // Pending, Reserved, Converted, Cancelled
            $table->date('reservation_date');
            $table->date('deadline_date')->nullable();
            $table->decimal('amount', 15, 2);
            $table->text('notes')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('lot_id');
            $table->index('buyer_master_list_id');

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('lot_id')->references('id')->on('lots')->restrictOnDelete();
            $table->foreign('buyer_master_list_id')->references('id')->on('buyer_master_lists')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
