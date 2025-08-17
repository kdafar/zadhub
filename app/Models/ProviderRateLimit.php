<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderRateLimit extends Model
{
    protected $fillable = [
        'provider_id',
        'key',
        'max_requests',
        'per_seconds',
        'window_started_at',
        'consumed',
        'meta',
    ];

    protected $casts = [
        'max_requests' => 'integer',
        'per_seconds' => 'integer',
        'window_started_at' => 'datetime',
        'consumed' => 'integer',
        'meta' => 'array',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }
}
