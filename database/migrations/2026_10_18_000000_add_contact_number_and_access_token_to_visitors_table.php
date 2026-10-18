<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            if (! Schema::hasColumn('visitors', 'contact_number')) {
                $table->string('contact_number', 30)->nullable();
            }
            if (! Schema::hasColumn('visitors', 'access_token')) {
                $table->string('access_token', 64)->nullable()->unique();
            }
        });
    }

    public function down(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            if (Schema::hasColumn('visitors', 'access_token')) {
                $table->dropColumn('access_token');
            }
            if (Schema::hasColumn('visitors', 'contact_number')) {
                $table->dropColumn('contact_number');
            }
        });
    }
};
