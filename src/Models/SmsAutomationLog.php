<?php

namespace MultiSms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SmsAutomationLog extends Model
{
    use HasFactory;

    protected $table = 'sms_automation_logs';

    protected $fillable = [
        'autoresponder_id',
        'trigger_id',
        'phone_number',
        'trigger_type',
        'trigger_value',
        'response_message',
        'status',
        'message_id',
        'driver_used',
        'execution_time_ms',
        'error_message',
        'context_data'
    ];

    protected $casts = [
        'execution_time_ms' => 'integer',
        'context_data' => 'array'
    ];

    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_PENDING = 'pending';

    /**
     * Get the autoresponder that owns this log
     */
    public function autoresponder()
    {
        return $this->belongsTo(SmsAutoresponder::class);
    }

    /**
     * Get the trigger that created this log
     */
    public function trigger()
    {
        return $this->belongsTo(SmsTrigger::class);
    }

    /**
     * Scope for successful executions
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    /**
     * Scope for failed executions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope for pending executions
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
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
     * Scope for specific driver
     */
    public function scopeByDriver($query, string $driver)
    {
        return $query->where('driver_used', $driver);
    }

    /**
     * Check if execution was successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    /**
     * Check if execution failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if execution is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Mark as successful
     */
    public function markAsSuccessful(string $messageId, string $driver, int $executionTimeMs = null): void
    {
        $this->update([
            'status' => self::STATUS_SUCCESS,
            'message_id' => $messageId,
            'driver_used' => $driver,
            'execution_time_ms' => $executionTimeMs
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $errorMessage, string $driver = null, int $executionTimeMs = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'driver_used' => $driver,
            'execution_time_ms' => $executionTimeMs
        ]);
    }

    /**
     * Get formatted execution time
     */
    public function getFormattedExecutionTimeAttribute(): string
    {
        if (is_null($this->execution_time_ms)) {
            return 'N/A';
        }
        
        if ($this->execution_time_ms < 1000) {
            return $this->execution_time_ms . 'ms';
        }
        
        return round($this->execution_time_ms / 1000, 2) . 's';
    }

    /**
     * Get formatted context data
     */
    public function getFormattedContextDataAttribute(): string
    {
        if (is_array($this->context_data)) {
            return json_encode($this->context_data, JSON_PRETTY_PRINT);
        }
        
        return $this->context_data ?? '';
    }
}