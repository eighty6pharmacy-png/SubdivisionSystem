<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('host_id')->nullable();
            $table->string('visitor_name');
            $table->string('visitor_address')->nullable();
            $table->string('contact_number', 30)->nullable();
            $table->string('pin')->nullable();
            $table->string('access_token', 64)->nullable()->unique();
            $table->string('purpose');
            $table->string('validity');
            $table->string('arrival_time')->nullable();
            $table->string('status')->default('Pending');
            $table->string('plate_number')->nullable();
            $table->string('type')->default('Pre-registered'); // Pre-registered, Walk-in, Appointment
            $table->uuid('guard_id')->nullable();
            $table->string('guard_name')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('host_id');
            $table->index('guard_id');

            $table->foreign('host_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('guard_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitors');
    }
};
