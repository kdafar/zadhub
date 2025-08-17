<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderRoutingRule extends Model
{
    protected $fillable = [
        'provider_id',
        'rule_type',
        'rule_config',
    ];

    protected $casts = [
        'rule_config' => 'array',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }
}
