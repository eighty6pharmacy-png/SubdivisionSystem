<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'utility_bill_id',
        'downpayment_id',
        'financing_record_id',
        'amount_paid',
        'method',
        'status',
        'trn',
        'payment_date',
        'paymongo_payment_id',
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

    public function downpayment(): BelongsTo
    {
        return $this->belongsTo(Downpayment::class);
    }

    public function financingRecord(): BelongsTo
    {
        return $this->belongsTo(FinancingRecord::class);
    }
}
