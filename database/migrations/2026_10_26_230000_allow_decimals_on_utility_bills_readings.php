<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('utility_bills', function (Blueprint $table) {
            $table->decimal('previous_reading', 12, 2)->default(0)->change();
            $table->decimal('current_reading', 12, 2)->default(0)->change();
            $table->decimal('usage_value', 12, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('utility_bills', function (Blueprint $table) {
            $table->integer('previous_reading')->default(0)->change();
            $table->integer('current_reading')->default(0)->change();
            $table->integer('usage_value')->default(0)->change();
        });
    }
};
