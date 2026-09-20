<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visitor extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'host_id',
        'visitor_name',
        'pin',
        'purpose',
        'validity',
        'arrival_time',
        'status',
        'plate_number',
        'type',
        'visitor_address',
    ];

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }
}
