<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reservations') && ! Schema::hasColumn('reservations', 'receipt_data')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->json('receipt_data')->nullable()->after('notes');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('reservations') && Schema::hasColumn('reservations', 'receipt_data')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->dropColumn('receipt_data');
            });
        }
    }
};
