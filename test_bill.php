<?php
try {
    \App\Models\UtilityBill::create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'type' => 'electricity',
        'user_id' => \App\Models\User::first()->id,
        'lot_id' => \App\Models\Lot::first()->id,
        'previous_reading' => 0,
        'current_reading' => 0,
        'usage_value' => 0,
        'amount' => 0,
        'previous_balance' => 0,
        'due_date' => now(),
        'status' => 'unpaid',
        'is_at_risk' => false
    ]);
    echo 'SUCCESS';
} catch (\Exception $e) {
    echo $e->getMessage();
}
