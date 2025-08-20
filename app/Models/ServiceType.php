<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ServiceType extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'service_types';

    protected $fillable = [
        'code',
        'slug',
        'name',
        'description',
        'default_locale',
        'meta',
        'is_active',
        'default_flow_template_id',
    ];

    protected $casts = [
        'meta' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (ServiceType $serviceType) {
            if (blank($serviceType->slug)) {
                $basis = $serviceType->code ?: $serviceType->name ?: uniqid('svc_', true);
                $serviceType->slug = Str::slug($basis);
            }
        });
    }

    /** Relationships */
    public function flowTemplates()
    {
        return $this->hasMany(FlowTemplate::class, 'service_type_id');
    }

    public function defaultFlowTemplate()
    {
        return $this->belongsTo(FlowTemplate::class, 'default_flow_template_id');
    }

    public function providers()
    {
        return $this->hasMany(Provider::class, 'service_type_id');
    }

    public function providerFlows()
    {
        return $this->hasMany(ProviderFlow::class, 'service_type_id');
    }

    public function keywords()
    {
        return $this->hasMany(ServiceKeyword::class, 'service_type_id');
    }

    /** Scopes */
    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
