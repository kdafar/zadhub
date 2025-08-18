<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlowTrigger extends Model
{
    protected $fillable = [
        'keyword', 'service_type_id', 'provider_id', 'flow_version_id',
        'use_latest_published', 'locale', 'priority', 'is_active',
    ];

    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class, 'service_type_id');
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function flowVersion()
    {
        return $this->belongsTo(FlowVersion::class);
    }
}
