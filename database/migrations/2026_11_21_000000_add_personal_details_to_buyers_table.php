<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buyers', function (Blueprint $table) {
            if (! Schema::hasColumn('buyers', 'birth_date')) {
                $table->date('birth_date')->nullable()->after('lot_id');
            }
            if (! Schema::hasColumn('buyers', 'age')) {
                $table->unsignedSmallInteger('age')->nullable()->after('birth_date');
            }
            if (! Schema::hasColumn('buyers', 'sex')) {
                $table->string('sex', 20)->nullable()->after('age');
            }
            if (! Schema::hasColumn('buyers', 'tin_number')) {
                $table->string('tin_number', 50)->nullable()->after('pagibig_number');
            }
        });
    }

    public function down(): void
    {
        $columns = array_values(array_filter(
            ['birth_date', 'age', 'sex', 'tin_number'],
            fn (string $column) => Schema::hasColumn('buyers', $column)
        ));

        if ($columns !== []) {
            Schema::table('buyers', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
