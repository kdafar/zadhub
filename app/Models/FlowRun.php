<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlowRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'whatsapp_session_id',
        'provider_flow_id',
        'flow_version_id',
        'status',            // running | completed | error
        'started_at',
        'completed_at',
        'last_screen_id',
        'data',              // working memory of the run (JSON)
        'error_message',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'data' => 'array',
    ];

    /** Relationships */
    public function session()
    {
        return $this->belongsTo(WhatsappSession::class, 'whatsapp_session_id');
    }

    public function providerFlow()
    {
        return $this->belongsTo(ProviderFlow::class);
    }

    public function version()
    {
        return $this->belongsTo(FlowVersion::class, 'flow_version_id');
    }

    public function steps()
    {
        return $this->hasMany(FlowRunStep::class);
    }
}
