<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Order extends Model
{
    use UsesTenantConnection;

    protected $fillable = [
        'session_id',
        'provider_id',
        'service_type_id',
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

    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class, 'service_type_id');
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
