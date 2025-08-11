<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlowVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'flow_template_id',
        'version_number',
        'json_definition',
        'builder_data',
        'approved_at',
    ];

    protected $casts = [
        'builder_data' => 'array',
        'approved_at' => 'datetime',
    ];

    public function flowTemplate(): BelongsTo
    {
        return $this->belongsTo(FlowTemplate::class);
    }
}
