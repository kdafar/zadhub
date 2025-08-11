<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_phone_number',
        'provider_id',
        'current_flow_id',
        'current_step_uuid',
        'status',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function currentFlow(): BelongsTo
    {
        return $this->belongsTo(Flow::class, 'current_flow_id');
    }
}
