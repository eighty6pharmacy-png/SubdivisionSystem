<?php

use Database\Seeders\HouseSpecificationSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Populate default 3M-4.5M house specifications on lots missing spec data so
     * Downpayment and Reservation Receipt pages sync lot pricing automatically.
     */
    public function up(): void
    {
        app(HouseSpecificationSeeder::class)->run();
    }

    public function down(): void
    {
        // Non-destructive data backfill; no rollback needed.
    }
};
