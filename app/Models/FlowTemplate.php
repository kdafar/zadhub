<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlowTemplate extends Model
{
    use HasFactory;

    protected $fillable = ['service_id', 'name', 'is_active', 'live_version_id'];

    protected $casts = ['is_active' => 'boolean'];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(FlowVersion::class)->orderBy('version_number', 'desc');
    }

    public function liveVersion(): BelongsTo
    {
        return $this->belongsTo(FlowVersion::class, 'live_version_id');
    }
}
