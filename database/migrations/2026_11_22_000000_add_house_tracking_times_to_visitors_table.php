<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            if (! Schema::hasColumn('visitors', 'house_arrived_at')) {
                $table->string('house_arrived_at')->nullable()->after('is_at_destination');
            }
            if (! Schema::hasColumn('visitors', 'left_house_at')) {
                $table->string('left_house_at')->nullable()->after('house_arrived_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            $cols = ['house_arrived_at', 'left_house_at'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('visitors', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
