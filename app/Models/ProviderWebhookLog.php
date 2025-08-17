<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderWebhookLog extends Model
{
    protected $fillable = [
        'provider_id',
        'direction',   // incoming|outgoing
        'endpoint',
        'request',
        'response',
        'status_code',
        'meta',
    ];

    protected $casts = [
        'request' => 'array',
        'response' => 'array',
        'status_code' => 'integer',
        'meta' => 'array',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }
}
