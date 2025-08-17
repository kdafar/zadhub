<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'session_id',
        'provider_id',
        'service_id',
        'external_order_id',
        'status',
        'subtotal',
        'delivery_fee',
        'discount',
        'total',
        'snapshot',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    // Your sessions table is "whatsapp_sessions", column is session_id
    public function whatsappSession()
    {
        return $this->belongsTo(WhatsappSession::class, 'session_id');
    }

    public function paymentLink()
    {
        return $this->hasOne(PaymentLink::class);
    }
}
