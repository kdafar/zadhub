<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WhatsappSession extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_sessions';

    protected $fillable = [
        'phone',
        'status',
        'locale',
        'service_type_id',
        'provider_id',
        'flow_version_id',
        'current_screen',
        'flow_token',
        'context',
        'last_interacted_at',
        'flow_history',
        'last_message_type',
        'last_payload',
        'ended_at',
        'ended_reason',
    ];

    protected $casts = [
        'context' => 'array',
        'flow_history' => 'array',
        'last_payload' => 'array',
        'last_interacted_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    // --- Relationships
    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class, 'service_type_id');
    }

    public function flowVersion()
    {
        return $this->belongsTo(FlowVersion::class);
    }

    // --- Compatibility accessors for older code that uses customer_phone_number
    public function getCustomerPhoneNumberAttribute(): ?string
    {
        return $this->phone;
    }

    public function setCustomerPhoneNumberAttribute($value): void
    {
        $this->attributes['phone'] = $value;
    }

    public function appendHistory(array $entry): void
    {
        $history = $this->flow_history ?? [];
        $entry = array_merge([
            'id' => (string) Str::uuid(),
            'at' => now()->toIso8601String(),
            'screen' => $this->current_screen,
            'event' => 'debug',   // e.g. message_received | screen_changed | session_reset | session_ended
            'meta' => [],
        ], $entry);

        $history[] = $entry;
        $this->flow_history = $history;
        $this->save();
    }

    public function end(string $reason = 'ended_by_admin'): void
    {
        $this->status = 'ended';
        $this->ended_at = now();
        $this->ended_reason = $reason;
        $this->appendHistory(['event' => 'session_ended', 'meta' => ['reason' => $reason]]);
    }

    public function resetSession(): void
    {
        $this->current_screen = null;
        $this->flow_token = null;
        $this->context = [];
        $this->flow_history = [];
        $this->status = 'active';
        $this->ended_at = null;
        $this->ended_reason = null;
        $this->appendHistory(['event' => 'session_reset']);
    }

    public function jumpToScreen(?string $screenId, array $meta = []): void
    {
        $this->current_screen = $screenId;
        $this->appendHistory(['event' => 'screen_jumped', 'screen' => $screenId, 'meta' => $meta]);
    }

    public function scopeActive($q)
    {
        return $q->where('status', 'active');
    }

    public function scopeRecentlyActive($q, $mins = 15)
    {
        return $q->where('last_interacted_at', '>=', now()->subMinutes($mins));
    }

    public function scopeForProvider($q, $pid)
    {
        return $q->where('provider_id', $pid);
    }

    public function scopeForService($q, $sid)
    {
        return $q->where('service_id', $sid);
    }
}
