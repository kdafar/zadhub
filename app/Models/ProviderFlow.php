<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderFlow extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'service_id',
        'flow_template_id',
        'active_version_id',
        'trigger_keyword',     // per-provider entry keyword
        'overrides',           // JSON for provider-specific tweaks
        'is_active',
        'meta',
    ];

    protected $casts = [
        'overrides' => 'array',
        'meta' => 'array',
        'is_active' => 'boolean',
    ];

    /** Relationships */
    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function template()
    {
        return $this->belongsTo(FlowTemplate::class, 'flow_template_id');
    }

    public function activeVersion()
    {
        return $this->belongsTo(FlowVersion::class, 'active_version_id');
    }

    public function flowRuns()
    {
        return $this->hasMany(FlowRun::class);
    }

    /** Scopes */
    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
