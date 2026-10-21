<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lot extends Model
{
    protected $fillable = [
        'block',
        'lot_number',
        'status',
        'provider_managed',
        'lot_area',
        'floor_area',
        'description',
        'tct_no',
        'pid',
        'tax_dec_no',
    ];

    protected function casts(): array
    {
        return [
            'provider_managed' => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'resident_lots', 'lot_id', 'resident_id')->withTimestamps();
    }

    public function buyer(): HasOne
    {
        return $this->hasOne(BuyerMasterList::class, 'lot_id');
    }

    public function utilityBills(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UtilityBill::class, 'lot_id');
    }

    public function reservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'lot_id');
    }

    public function financingRecord(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(FinancingRecord::class, 'lot_id');
    }
}
