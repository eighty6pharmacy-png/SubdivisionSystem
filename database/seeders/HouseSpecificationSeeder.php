<?php

namespace Database\Seeders;

use App\Models\Lot;
use Illuminate\Database\Seeder;

class HouseSpecificationSeeder extends Seeder
{
    /**
     * Populate sample house specifications (3M - 4.5M tiers) for lots missing spec data.
     */
    public function run(): void
    {
        $models = [
            [
                'description' => 'Althea Classic (2-Storey Townhouse, 2BR)',
                'lot_area' => 80.00,
                'floor_area' => 60.00,
                'contract_price' => 3000000.00,
            ],
            [
                'description' => 'Bettina Premier (2-Storey Duplex, 3BR)',
                'lot_area' => 100.00,
                'floor_area' => 78.00,
                'contract_price' => 3500000.00,
            ],
            [
                'description' => 'Clara Executive (2-Storey Single-Attached, 3BR)',
                'lot_area' => 120.00,
                'floor_area' => 95.00,
                'contract_price' => 4000000.00,
            ],
            [
                'description' => 'Diana Grand (2-Storey Single-Detached, 4BR)',
                'lot_area' => 150.00,
                'floor_area' => 120.00,
                'contract_price' => 4500000.00,
            ],
        ];

        $lots = Lot::orderBy('block')->orderBy('lot_number')->get();

        foreach ($lots as $index => $lot) {
            $spec = $models[$index % count($models)];
            $updates = [];

            if ($lot->contract_price === null || (float) $lot->contract_price <= 0) {
                $updates['contract_price'] = $spec['contract_price'];
            }
            if ($lot->lot_area === null || (float) $lot->lot_area <= 0) {
                $updates['lot_area'] = $spec['lot_area'];
            }
            if ($lot->floor_area === null || (float) $lot->floor_area <= 0) {
                $updates['floor_area'] = $spec['floor_area'];
            }
            if (empty(trim((string) $lot->description))) {
                $updates['description'] = $spec['description'];
            }

            if ($updates !== []) {
                $lot->update($updates);
            }
        }
    }
}
