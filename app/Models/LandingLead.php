<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingLead extends Model
{
    protected $fillable = [
        'name', 'company', 'email', 'phone', 'use_case', 'locale', 'message', 'utm', 'ip',
    ];

    protected $casts = [
        'utm' => 'array',
    ];
}
