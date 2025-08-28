<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class ProviderCredential extends Model
{
    use HasFactory, UsesTenantConnection;

    protected $fillable = [
        'provider_id',
        'key_name',
        'secret_encrypted',     // consider encryption/casting if sensitive
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }
}
