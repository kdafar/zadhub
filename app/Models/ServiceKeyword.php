<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceKeyword extends Model
{
    protected $fillable = [
        'service_type_id',
        'keyword',
        'locale',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class, 'service_type_id');
    }
}
