<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderFlowPin extends Model
{
    protected $fillable = [
        'provider_id',
        'flow_template_id',
        'flow_version_id', // pinned version for this provider
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function flowTemplate()
    {
        return $this->belongsTo(FlowTemplate::class);
    }

    public function pinnedVersion()
    {
        return $this->belongsTo(FlowVersion::class, 'pinned_version_id');
    }
}
