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
        Schema::create('visitors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('host_id')->nullable();
            $table->string('visitor_name');
            $table->string('pin')->nullable();
            $table->string('purpose');
            $table->string('validity');
            $table->string('arrival_time')->nullable();
            $table->string('status')->default('Pending');
            $table->string('plate_number')->nullable();
            $table->string('type')->default('Pre-registered'); // Pre-registered or Walk-in
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('host_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitors');
    }
};
