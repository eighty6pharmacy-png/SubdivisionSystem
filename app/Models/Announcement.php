<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'title',
        'category',
        'content',
        'author',
        'status',
        'publish_date',
    ];

    protected function casts(): array
    {
        return [
            'publish_date' => 'date',
        ];
    }
}
