<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProviderService extends Pivot
{
    protected $table = 'provider_service';

    protected $fillable = [
        'provider_id',
        'service_id',
        'trigger_keyword',
        'status',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    /** Relationships (optional helpers) */
    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
