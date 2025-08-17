<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Flow extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'name',
        'is_active',
        'meta',
        'trigger_keyword',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function liveVersion(): HasOne
    {
        return $this->hasOne(FlowVersion::class, 'flow_id')
            ->where('status', 'published')
            ->orderByDesc('published_at'); // or ->orderByDesc('version')
    }

    public function versions(): HasMany
    {
        return $this->hasMany(FlowVersion::class, 'flow_id');
    }
}
