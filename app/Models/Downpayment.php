<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Downpayment extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'reservation_id',
        'amount',
        'due_date',
        'status',
        'balance',
        'monthly_amortization',
        'months_to_pay',
        'contract_date',
        'penalty_percentage',
        'paymongo_checkout_id',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(DownpaymentHistory::class);
    }

    public function latePenaltyAmount(float $principal, $dueDate = null): float
    {
        $due = $dueDate ? Carbon::parse($dueDate)->startOfDay() : ($this->due_date?->copy()->startOfDay());
        if (!$due || $due->gte(now()->startOfDay())) {
            return 0;
        }

        $rate = (float) ($this->penalty_percentage ?? 0);
        if ($rate <= 0 || $principal <= 0) {
            return 0;
        }

        return round($principal * ($rate / 100), 2);
    }
}
