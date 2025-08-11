<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Flow extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'flow_template_id',
        'live_version_id',
        'trigger_keyword',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(FlowTemplate::class, 'flow_template_id');
    }

    public function liveVersion(): BelongsTo
    {
        return $this->belongsTo(FlowVersion::class, 'live_version_id');
    }
}
