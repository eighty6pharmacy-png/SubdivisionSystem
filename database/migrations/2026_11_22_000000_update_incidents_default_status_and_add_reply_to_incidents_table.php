<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->string('status')->default('no_action')->change();

            if (! Schema::hasColumn('incidents', 'admin_reply')) {
                $table->text('admin_reply')->nullable()->after('admin_notes');
            }
            if (! Schema::hasColumn('incidents', 'replied_at')) {
                $table->timestamp('replied_at')->nullable()->after('admin_reply');
            }
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->string('status')->default('progress')->change();

            $columns = array_values(array_filter(
                ['admin_reply', 'replied_at'],
                fn (string $col) => Schema::hasColumn('incidents', $col)
            ));
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
