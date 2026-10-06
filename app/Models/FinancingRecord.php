<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class FinancingRecord extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected $casts = [
        'loan_release_date' => 'date',
    ];

    public function buyerMasterList(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(BuyerMasterList::class, 'buyer_master_list_id');
    }

    public function lot(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }
}
