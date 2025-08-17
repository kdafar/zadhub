<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionState extends Model
{
    protected $fillable = [
        'whatsapp_session_id',
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    public function session()
    {
        return $this->belongsTo(WhatsappSession::class, 'whatsapp_session_id');
    }
}
