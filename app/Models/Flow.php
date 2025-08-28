<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Flow extends Model
{
    use HasFactory, UsesTenantConnection;

    protected $fillable = [
        'provider_id',
        'name',
        'is_active',
        'meta',
        'trigger_keyword',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function liveVersion()
    {
        return $this->hasOne(\App\Models\FlowVersion::class, 'flow_id', 'id')
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(FlowVersion::class, 'flow_id');
    }
}
