<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Automation extends Model
{
    protected $fillable = [
        'provider_id',
        'name',
        'trigger_event',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function steps()
    {
        return $this->hasMany(AutomationStep::class)->orderBy('order');
    }
}
