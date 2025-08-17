<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderFlowOverride extends Model
{
    protected $fillable = [
        'provider_id',
        'flow_version_id',
        'overrides_json',
    ];

    protected $casts = [
        'overrides_json' => 'array',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function flowVersion()
    {
        return $this->belongsTo(FlowVersion::class);
    }

    // Nice label for tables
    public function getFlowVersionLabelAttribute(): string
    {
        $v = $this->flowVersion;
        if (! $v) {
            return '—';
        }

        $tplName = $v->template->name ?? 'Template';

        return $tplName.' • v'.$v->version.($v->is_stable ? ' (stable)' : '');
    }
}
