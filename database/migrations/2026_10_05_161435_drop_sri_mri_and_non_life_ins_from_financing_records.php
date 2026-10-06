ey
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('financing_records', function (Blueprint $table) {
            $table->dropColumn(['sri_mri', 'non_life_insurance']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financing_records', function (Blueprint $table) {
            $table->decimal('sri_mri', 15, 2)->default(0);
            $table->decimal('non_life_insurance', 15, 2)->default(0);
        });
    }
};
