<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsEvent extends Model
{
    protected $fillable = [
        'whatsapp_session_id',
        'provider_id',
        'service_type_id',
        'name',       // e.g. flow_started, screen_view, error, order_submitted
        'properties', // JSON payload
        'occurred_at',
    ];

    protected $casts = [
        'properties' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(WhatsappSession::class, 'whatsapp_session_id');
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class, 'service_type_id');
    }
}
