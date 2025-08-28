<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Provider extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'service_type_id',
        'name',
        'slug',
        'status',
        'api_base_url',
        'auth_type',
        'is_sandbox',
        'locale_defaults',
        'feature_flags',
        // new:
        'is_active',
        'callback_url',
        'contact_email',
        'contact_phone',
        'timezone',
        'whatsapp_phone_number_id',
        'api_token',
        'meta',
    ];

    protected $casts = [
        'is_sandbox' => 'boolean',
        'is_active' => 'boolean',
        'locale_defaults' => 'array',
        'feature_flags' => 'array',
        'meta' => 'array',
    ];

    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class, 'service_type_id');
    }

    public function credentials()
    {
        return $this->hasMany(ProviderCredential::class);
    }

    public function routingRules()
    {
        return $this->hasMany(ProviderRoutingRule::class);
    }

    public function flowPins()
    {
        return $this->hasMany(ProviderFlowPin::class);
    }

    public function flowOverrides()
    {
        return $this->hasMany(ProviderFlowOverride::class);
    }

    public function webhookLogs()
    {
        return $this->hasMany(ProviderWebhookLog::class);
    }

    public function healthChecks()
    {
        return $this->hasMany(ProviderHealthCheck::class);
    }

    public function rateLimits()
    {
        return $this->hasMany(ProviderRateLimit::class);
    }

    public function apiLogs()
    {
        return $this->hasMany(AdapterApiLog::class);
    }

    public function whatsappSessions()
    {
        return $this->hasMany(WhatsappSession::class);
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function flows()
    {
        return $this->hasMany(Flow::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';  // use 'slug' instead of 'id'
    }
}
