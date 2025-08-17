<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = [
        'session_id',
        'currency',
        'meta',
    ];

    public function whatsappSession()
    {
        return $this->belongsTo(WhatsappSession::class, 'session_id');
    }
}
