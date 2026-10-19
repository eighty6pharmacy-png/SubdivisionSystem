<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            if (Schema::hasColumn('incidents', 'image_url')) {
                $table->text('image_url')->nullable()->change();
            }
            if (! Schema::hasColumn('incidents', 'video_url')) {
                $table->text('video_url')->nullable()->after('image_url');
            }
            if (! Schema::hasColumn('incidents', 'admin_notes')) {
                $table->text('admin_notes')->nullable()->after('video_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            if (Schema::hasColumn('incidents', 'admin_notes')) {
                $table->dropColumn('admin_notes');
            }
            if (Schema::hasColumn('incidents', 'video_url')) {
                $table->dropColumn('video_url');
            }
            if (Schema::hasColumn('incidents', 'image_url')) {
                $table->string('image_url', 255)->nullable()->change();
            }
        });
    }
};
