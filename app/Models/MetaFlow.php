<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetaFlow extends Model
{
    protected $fillable = ['flow_version_id', 'meta_flow_id', 'status', 'template_name', 'published_at', 'last_payload'];

    protected $casts = ['last_payload' => 'array', 'published_at' => 'datetime'];

    public function flowVersion()
    {
        return $this->belongsTo(FlowVersion::class);
    }
}
