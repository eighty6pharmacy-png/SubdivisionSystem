<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DownpaymentHistory extends Model
{
    use HasUuids;

    protected $table = 'downpayment_histories';

    protected $fillable = [
        'downpayment_id',
        'amount',
        'payment_date',
        'trn',
        'payment_method',
        'status',
        'paymongo_payment_id',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'datetime',
        ];
    }

    public function downpayment(): BelongsTo
    {
        return $this->belongsTo(Downpayment::class);
    }
}
