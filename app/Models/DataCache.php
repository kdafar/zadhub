<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataCache extends Model
{
    protected $fillable = [
        'key',
        'value',
        'meta',
        'expires_at',
    ];

    protected $casts = [
        'value' => 'array',
        'meta' => 'array',
        'expires_at' => 'datetime',
    ];
}
