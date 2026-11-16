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
        Schema::table('reservations', function (Blueprint $table) {
            if (! Schema::hasColumn('reservations', 'expiry_reminder_5d_sent_at')) {
                $table->timestamp('expiry_reminder_5d_sent_at')->nullable()->after('deadline_date');
            }
            if (! Schema::hasColumn('reservations', 'expired_reminder_sent_at')) {
                $table->timestamp('expired_reminder_sent_at')->nullable()->after('expiry_reminder_5d_sent_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $columns = array_filter(
                ['expiry_reminder_5d_sent_at', 'expired_reminder_sent_at'],
                fn (string $col) => Schema::hasColumn('reservations', $col)
            );
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
