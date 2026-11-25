<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reservations')) {
            $columns = array_values(array_filter(
                ['first_name', 'last_name'],
                fn (string $column): bool => Schema::hasColumn('reservations', $column)
            ));

            if ($columns !== []) {
                Schema::table('reservations', function (Blueprint $table) use ($columns): void {
                    $table->dropColumn($columns);
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('reservations')) {
            Schema::table('reservations', function (Blueprint $table): void {
                if (! Schema::hasColumn('reservations', 'first_name')) {
                    $table->string('first_name')->nullable()->after('buyer_id');
                }
                if (! Schema::hasColumn('reservations', 'last_name')) {
                    $table->string('last_name')->nullable()->after('first_name');
                }
            });
        }
    }
};
