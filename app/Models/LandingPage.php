<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingPage extends Model
{
    protected $fillable = [
        'slug', 'locale', 'title', 'meta_title', 'meta_description', 'sections', 'is_published', 'published_at', 'version',
    ];

    protected $casts = [
        'sections' => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];
}
