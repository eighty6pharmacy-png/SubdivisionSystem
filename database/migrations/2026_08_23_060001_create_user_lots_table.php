<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_lots', function (Blueprint $table) {
            $table->uuid('user_id');
            $table->uuid('lot_id');
            $table->timestamps();

            $table->primary(['user_id', 'lot_id']);
            $table->index('lot_id');

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('lot_id')->references('id')->on('lots')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_lots');
    }
};
