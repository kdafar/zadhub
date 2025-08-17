<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderHealthCheck extends Model
{
    protected $fillable = [
        'provider_id',
        'status',     // up|degraded|down
        'latency_ms',
        'details',
        'meta',
    ];

    protected $casts = [
        'latency_ms' => 'integer',
        'details' => 'array',
        'meta' => 'array',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }
}
