<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class FlowTrigger extends Model
{
    use HasFactory, UsesTenantConnection;

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
