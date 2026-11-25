<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['downpayments', 'utility_bills'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'reminder_5d_sent_at')) {
                    $table->timestamp('reminder_5d_sent_at')->nullable();
                }
                if (! Schema::hasColumn($tableName, 'overdue_reminder_sent_at')) {
                    $table->timestamp('overdue_reminder_sent_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['downpayments', 'utility_bills'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $cols = array_filter(
                    ['reminder_5d_sent_at', 'overdue_reminder_sent_at'],
                    fn ($col) => Schema::hasColumn($tableName, $col)
                );
                if ($cols !== []) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
