<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnouncementRecipient extends Model
{
    protected $table = 'announcement_recipients';

    protected $primaryKey = 'announcement_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'announcement_id',
        'resident_id',
    ];

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class, 'announcement_id');
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class, 'resident_id', 'user_id');
    }
}
