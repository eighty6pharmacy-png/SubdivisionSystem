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
        'video_url',
        'admin_notes',
        'status',
    ];

    protected static function booted()
    {
        static::forceDeleted(function ($incident) {
            $incident->deleteMediaFiles();
        });
    }

    public function deleteMediaFiles(): void
    {
        if ($this->image_url) {
            $decoded = json_decode($this->image_url, true);
            $photos = is_array($decoded) ? $decoded : [$this->image_url];
            foreach ($photos as $photo) {
                $clean = str_replace('/storage/', '', $photo);
                \Illuminate\Support\Facades\Storage::disk('public')->delete($clean);
            }
        }
        if ($this->video_url) {
            $cleanVideo = str_replace('/storage/', '', $this->video_url);
            \Illuminate\Support\Facades\Storage::disk('public')->delete($cleanVideo);
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resident_id');
    }
}
