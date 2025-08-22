<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlowTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_type_id',
        'name',
        'slug',
        'description',
        'schema',     // JSON definition of screens/components
        'is_active',
        'versioning_strategy', // optional
        'meta',
        'latest_version_id',
    ];

    protected $casts = [
        'schema' => 'array',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class, 'service_type_id');
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
