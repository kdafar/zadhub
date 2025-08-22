<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model; // ✅ import
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlowVersion extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'flow_id',
        'flow_template_id',
        'service_type_id',
        'provider_id',
        'name',
        'version',
        'status',
        'is_template',
        'is_stable',
        'published_at',
        'definition',
        'schema_json',
        'components_json',
        'meta',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'definition' => 'array',
        'builder_data' => 'array',
        'schema_json' => 'array',
        'components_json' => 'array',
    ];

    public function flow(): BelongsTo
    {
        return $this->belongsTo(Flow::class);
    }

    // 🔁 Relations
    public function template()
    {
        return $this->belongsTo(FlowTemplate::class, 'flow_template_id');
    }

    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class, 'service_type_id');
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function metaFlow()
    {
        return $this->hasOne(\App\Models\MetaFlow::class, 'flow_version_id');
    }

    // 🔎 Scopes
    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', 'published')->whereNotNull('published_at');
    }

    public function scopeFor(Builder $q, ?int $serviceId, ?int $providerId): Builder
    {
        return $q->when($serviceId, fn ($x) => $x->where('service_id', $serviceId))
            ->when($providerId, fn ($x) => $x->where('provider_id', $providerId));
    }

    // 🧰 Back-compat: expose `definition` as `builder_data`
    public function getBuilderDataAttribute(): array
    {
        return $this->definition ?? $this->schema_json ?? [];
    }
}
