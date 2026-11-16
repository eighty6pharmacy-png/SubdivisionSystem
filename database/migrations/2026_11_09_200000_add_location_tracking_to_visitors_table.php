<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            if (! Schema::hasColumn('visitors', 'current_lat')) {
                $table->decimal('current_lat', 10, 8)->nullable();
            }
            if (! Schema::hasColumn('visitors', 'current_lng')) {
                $table->decimal('current_lng', 11, 8)->nullable();
            }
            if (! Schema::hasColumn('visitors', 'last_ping_at')) {
                $table->timestamp('last_ping_at')->nullable();
            }
            if (! Schema::hasColumn('visitors', 'is_at_destination')) {
                $table->boolean('is_at_destination')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            $cols = ['current_lat', 'current_lng', 'last_ping_at', 'is_at_destination'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('visitors', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
