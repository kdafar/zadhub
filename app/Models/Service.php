<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory;

    // If your table does NOT have a deleted_at column yet, remove SoftDeletes or add a migration to add it.
    use SoftDeletes;

    protected $fillable = [
        'code',                    // 👈 new
        'slug',
        'name',
        'description',
        'default_locale',          // 👈 new
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
        static::creating(function (Service $service) {
            if (blank($service->slug)) {
                $basis = $service->code ?: $service->name ?: uniqid('svc_', true);
                $service->slug = \Str::slug($basis);
            }
        });
    }

    /** Relationships */
    public function flowTemplates()
    {
        return $this->hasMany(FlowTemplate::class);
    }

    public function defaultFlowTemplate()
    {
        return $this->belongsTo(FlowTemplate::class, 'default_flow_template_id');
    }

    public function providers()
    {
        return $this->hasMany(\App\Models\Provider::class);
    }

    public function providerFlows()
    {
        return $this->hasMany(ProviderFlow::class);
    }

    public function keywords()
    {
        return $this->hasMany(ServiceKeyword::class);
    }

    /** Scopes */
    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
