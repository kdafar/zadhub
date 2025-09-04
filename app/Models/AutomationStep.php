<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomationStep extends Model
{
    protected $fillable = [
        'automation_id',
        'order',
        'delay_minutes',
        'action_type',
        'action_config',
        'conditions',
    ];

    protected $casts = [
        'action_config' => 'array',
        'conditions' => 'array',
    ];

    public function automation()
    {
        return $this->belongsTo(Automation::class);
    }
}
