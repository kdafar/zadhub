<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceKeyword extends Model
{
    protected $fillable = [
        'service_id',
        'keyword',
        'locale',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
