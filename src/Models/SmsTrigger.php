<?php

namespace MultiSms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SmsTrigger extends Model
{
    use HasFactory;

    protected $table = 'sms_triggers';

    protected $fillable = [
        'autoresponder_id',
        'phone_number',
        'trigger_type',
        'trigger_data',
        'response_sent',
        'response_message_id',
        'processed_at',
        'error_message'
    ];

    protected $casts = [
        'trigger_data' => 'array',
        'response_sent' => 'boolean',
        'processed_at' => 'datetime'
    ];

    /**
     * Get the autoresponder that owns this trigger
     */
    public function autoresponder()
    {
        return $this->belongsTo(SmsAutoresponder::class);
    }

    /**
     * Scope for processed triggers
     */
    public function scopeProcessed($query)
    {
        return $query->whereNotNull('processed_at');
    }

    /**
     * Scope for unprocessed triggers
     */
    public function scopeUnprocessed($query)
    {
        return $query->whereNull('processed_at');
    }

    /**
     * Scope for successful responses
     */
    public function scopeSuccessful($query)
    {
        return $query->where('response_sent', true);
    }

    /**
     * Scope for failed responses
     */
    public function scopeFailed($query)
    {
        return $query->where('response_sent', false)
                    ->whereNotNull('processed_at');
    }

    /**
     * Scope for specific phone number
     */
    public function scopeForPhoneNumber($query, string $phoneNumber)
    {
        return $query->where('phone_number', $phoneNumber);
    }

    /**
     * Scope for specific trigger type
     */
    public function scopeByTriggerType($query, string $triggerType)
    {
        return $query->where('trigger_type', $triggerType);
    }

    /**
     * Mark trigger as processed
     */
    public function markAsProcessed(bool $responseSent = true, ?string $messageId = null, ?string $errorMessage = null): void
    {
        $this->update([
            'processed_at' => now(),
            'response_sent' => $responseSent,
            'response_message_id' => $messageId,
            'error_message' => $errorMessage
        ]);
    }

    /**
     * Check if trigger is processed
     */
    public function isProcessed(): bool
    {
        return !is_null($this->processed_at);
    }

    /**
     * Check if response was sent successfully
     */
    public function isSuccessful(): bool
    {
        return $this->response_sent && $this->isProcessed();
    }

    /**
     * Check if trigger failed
     */
    public function isFailed(): bool
    {
        return !$this->response_sent && $this->isProcessed();
    }

    /**
     * Get formatted trigger data
     */
    public function getFormattedTriggerDataAttribute(): string
    {
        if (is_array($this->trigger_data)) {
            return json_encode($this->trigger_data, JSON_PRETTY_PRINT);
        }
        
        return $this->trigger_data ?? '';
    }
}