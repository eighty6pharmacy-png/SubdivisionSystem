<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visitor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'resident_id',
        'host_name',
        'visitor_name',
        'contact_number',
        'pin',
        'access_token',
        'purpose',
        'validity',
        'arrival_time',
        'status',
        'plate_number',
        'type',
        'visitor_address',
        'guard_id',
        'guard_name',
    ];

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resident_id');
    }

    // Not named guard(): that would override Model::guard(array $guarded).
    public function guardUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guard_id');
    }
}
