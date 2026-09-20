<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'utility_bill_id',
        'amount_paid',
        'method',
        'trn',
        'payment_date',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'datetime',
        ];
    }

    public function utilityBill(): BelongsTo
    {
        return $this->belongsTo(UtilityBill::class);
    }
}
