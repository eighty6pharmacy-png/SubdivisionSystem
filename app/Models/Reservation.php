<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'lot_id',
        'buyer_id',
        'buyer_master_list_id',
        'status',
        'reservation_date',
        'deadline_date',
        'amount',
        'notes',
        'first_name',
        'last_name',
    ];

    protected function casts(): array
    {
        return [
            'reservation_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function downpayments(): HasMany
    {
        return $this->hasMany(Downpayment::class);
    }

    public function setBuyerMasterListIdAttribute($value): void
    {
        $this->attributes['buyer_id'] = $value;
        unset($this->attributes['buyer_master_list_id']);
    }

    public function getBuyerMasterListIdAttribute(): mixed
    {
        return $this->attributes['buyer_id'] ?? null;
    }

    public function buyerMasterList(): BelongsTo
    {
        return $this->belongsTo(BuyerMasterList::class, 'buyer_id', 'id');
    }

    public function buyer(): BelongsTo
    {
        return $this->buyerMasterList();
    }
}
