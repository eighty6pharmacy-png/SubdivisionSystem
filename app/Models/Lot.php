<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Lot extends Model
{
    use HasUuids;

    protected $fillable = [
        'block',
        'lot_number',
        'status',
        'provider_managed',
    ];

    protected function casts(): array
    {
        return [
            'provider_managed' => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_lots', 'lot_id', 'user_id')->withTimestamps();
    }
}
