<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlowTemplate extends Model
{
    protected $fillable = [
        'service_id',
        'name',
        'slug',
        'description',
        'schema',     // JSON definition of screens/components
        'is_active',
        'versioning_strategy', // optional
        'meta',
    ];

    protected $casts = [
        'schema' => 'array',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function versions()
    {
        return $this->hasMany(FlowVersion::class);
    }

    public function providerPins()
    {
        return $this->hasMany(ProviderFlowPin::class);
    }

    public function providerOverrides()
    {
        return $this->hasMany(ProviderFlowOverride::class);
    }

    public function latestVersion()
    {
        return $this->belongsTo(\App\Models\FlowVersion::class, 'latest_version_id');
    }

    public function pins()
    {
        return $this->hasMany(\App\Models\ProviderFlowPin::class);
    }
}
