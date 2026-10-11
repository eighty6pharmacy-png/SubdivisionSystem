<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancingPaymentHistory extends Model
{
    use HasUuids;

    protected $table = 'financing_payment_histories';

    protected $fillable = [
        'financing_record_id',
        'amount_paid',
        'payment_date',
        'trn',
        'status',
        'payment_method',
        'paymongo_payment_id',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'datetime',
        ];
    }

    public function financingRecord(): BelongsTo
    {
        return $this->belongsTo(FinancingRecord::class);
    }
}
