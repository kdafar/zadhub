<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model; // ✅ import

class FlowVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'flow_template_id',
        'version',
        'status',
        'definition',
        'schema_json',       // ✅ add
        'components_json',   // (optional)
        'changelog',
        'published_at',
        'meta',
        'service_id',
        'provider_id',
        'flow_id',
    ];

    protected $casts = [
        'definition' => 'array',
        'schema_json' => 'array',     // ✅ add
        'components_json' => 'array',     // (optional)
        'meta' => 'array',
        'published_at' => 'datetime',
    ];

    // 🔁 Relations
    public function template()
    {
        return $this->belongsTo(FlowTemplate::class, 'flow_template_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function flow()
    {
        return $this->belongsTo(Flow::class, 'flow_id');
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
