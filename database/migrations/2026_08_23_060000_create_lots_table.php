<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('block');
            $table->string('lot_number');
            $table->string('status')->default('Available');
            $table->boolean('provider_managed')->default(false);
            $table->timestamps();

            $table->unique(['block', 'lot_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lots');
    }
};
