<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaCache extends Model
{
    protected $fillable = [
        'key',
        'b64',
        'mime',
        'meta',
        'last_modified_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'last_modified_at' => 'datetime',
    ];

    public $timestamps = true;
}
