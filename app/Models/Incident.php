<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Incident extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'resident_id',
        'reporter_name',
        'subject',
        'type',
        'description',
        'image_url',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resident_id');
    }
}
