<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\User;
use App\Models\Lot;
use App\Models\UtilityBill;
use App\Models\Payment;
use Illuminate\Support\Str;

class PhaseTwoSeeder extends Seeder
{
    public function run(): void
    {
        $mockBills = [
            ['lot' => 'B1 L5', 'block' => '1', 'resident' => 'Juan Dela Cruz', 'amount' => 1250.50, 'usage_kwh' => 150, 'status' => 'unpaid', 'due' => '2026-05-10', 'at_risk' => false, 'payment_history' => [['amount' => 1200, 'date' => '2026-03-02']]],
            ['lot' => 'B2 L12', 'block' => '2', 'resident' => 'Maria Santos', 'amount' => 2100.00, 'usage_kwh' => 245, 'status' => 'unpaid', 'due' => '2026-04-10', 'at_risk' => true, 'payment_history' => [['amount' => 2000, 'date' => '2026-03-12']]],
            ['lot' => 'B3 L8', 'block' => '3', 'resident' => 'Ricardo Reyes', 'amount' => 890.75, 'usage_kwh' => 110, 'status' => 'paid', 'due' => '2026-04-05', 'at_risk' => false, 'payment_history' => [['amount' => 890.75, 'date' => '2026-04-05']]],
        ];

        foreach ($mockBills as $billData) {
            $user = User::where('name', $billData['resident'])->first();
            
            // Extract lot number correctly based on format "B1 L5"
            preg_match('/L(\d+)/', $billData['lot'], $matches);
            $lotNumber = isset($matches[1]) ? $matches[1] : null;
            $lot = Lot::where('block', $billData['block'])->where('lot_number', $lotNumber)->first();

            if ($lot) {
                // Create Electricity Bill
                $elecBill = UtilityBill::create([
                    'type' => 'electricity',
                    'user_id' => $user ? $user->id : null,
                    'lot_id' => $lot->id,
                    'usage_value' => $billData['usage_kwh'],
                    'amount' => $billData['amount'],
                    'due_date' => $billData['due'] === 'N/A' ? null : $billData['due'],
                    'status' => $billData['status'],
                    'is_at_risk' => $billData['at_risk'],
                ]);

                // Create Water Bill (Simulated)
                $waterBill = UtilityBill::create([
                    'type' => 'water',
                    'user_id' => $user ? $user->id : null,
                    'lot_id' => $lot->id,
                    'usage_value' => ceil($billData['usage_kwh'] / 5),
                    'amount' => ceil($billData['usage_kwh'] / 5) * 15,
                    'due_date' => $billData['due'] === 'N/A' ? null : $billData['due'],
                    'status' => $billData['status'],
                    'is_at_risk' => $billData['at_risk'],
                ]);

                // Create Payments
                foreach ($billData['payment_history'] as $hist) {
                    Payment::create([
                        'utility_bill_id' => $elecBill->id,
                        'amount_paid' => $hist['amount'],
                        'method' => 'Office',
                        'trn' => 'TRN-ELEC-' . strtoupper(Str::random(6)),
                        'payment_date' => $hist['date'],
                    ]);
                    Payment::create([
                        'utility_bill_id' => $waterBill->id,
                        'amount_paid' => ceil($hist['amount'] / 5),
                        'method' => 'Office',
                        'trn' => 'TRN-WATR-' . strtoupper(Str::random(6)),
                        'payment_date' => $hist['date'],
                    ]);
                }
            }
        }
    }
}
