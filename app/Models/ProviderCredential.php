<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderCredential extends Model
{
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
