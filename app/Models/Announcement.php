<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'category',
        'content',
        'author',
        'admin_id',
        'status',
        'publish_date',
    ];

    public function adminAccount(): BelongsTo
    {
        return $this->belongsTo(AdminAccount::class, 'admin_id', 'user_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(AnnouncementRecipient::class, 'announcement_id');
    }

    protected function casts(): array
    {
        return [
            'publish_date' => 'date',
        ];
    }
}
