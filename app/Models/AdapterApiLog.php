<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdapterApiLog extends Model
{
    protected $fillable = [
        'provider_id',
        'service_type_id',
        'adapter',   // e.g. restaurants, telecom, hospital
        'operation', // e.g. list_menu, get_balance, book_appointment
        'request',
        'response',
        'status_code',
        'latency_ms',
        'meta',
    ];

    protected $casts = [
        'request' => 'array',
        'response' => 'array',
        'status_code' => 'integer',
        'latency_ms' => 'integer',
        'meta' => 'array',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class, 'service_type_id');
    }
}
