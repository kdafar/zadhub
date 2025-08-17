<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'service_id',
        'base_url',
        'auth_type',     // bearer | basic | custom
        'api_key',
        'username',
        'password',
        'headers',       // JSON map of header => value
        'meta',
        'is_active',
    ];

    protected $casts = [
        'headers' => 'array',
        'meta' => 'array',
        'is_active' => 'boolean',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
