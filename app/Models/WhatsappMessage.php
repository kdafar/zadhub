<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'whatsapp_session_id',
        'wa_message_id',   // Meta message id
        'direction',       // incoming | outgoing
        'type',            // text | interactive | location | order | reaction | template
        'body',            // JSON payload as stored
        'meta',            // delivery receipts etc.
        'sent_at',
        'delivered_at',
        'read_at',
    ];

    protected $casts = [
        'body' => 'array',
        'meta' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    /** Relationships */
    public function session()
    {
        return $this->belongsTo(WhatsappSession::class, 'whatsapp_session_id');
    }
}
