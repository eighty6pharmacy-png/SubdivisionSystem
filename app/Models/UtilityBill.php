<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UtilityBill extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'type',
        'user_id',
        'lot_id',
        'previous_reading',
        'current_reading',
        'usage_value',
        'amount',
        'previous_balance',
        'due_date',
        'status',
        'is_at_risk',
    ];

    protected function casts(): array
    {
        return [
            'is_at_risk' => 'boolean',
            'due_date' => 'date',
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

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
