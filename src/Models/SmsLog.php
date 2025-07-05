<?php

namespace MultiSms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class SmsLog extends Model
{
    use HasFactory;

    protected $table = 'sms_logs';

    protected $fillable = [
        'to',
        'from',
        'body',
        'driver',
        'status',
        'response',
        'sent_at',
        'campaign_id',
        'template_id',
        'cost',
        'message_id',
        'error_message'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'response' => 'array',
        'cost' => 'decimal:4'
    ];

    /**
     * Get the campaign that owns the SMS log
     */
    public function campaign()
    {
        return $this->belongsTo(SmsCampaign::class);
    }

    /**
     * Get the template that was used for this SMS
     */
    public function template()
    {
        return $this->belongsTo(SmsTemplate::class);
    }

    /**
     * Scope for successful SMS
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope for failed SMS
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for specific driver
     */
    public function scopeByDriver($query, string $driver)
    {
        return $query->where('driver', $driver);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, Carbon $from, Carbon $to)
    {
        return $query->whereBetween('sent_at', [$from, $to]);
    }

    /**
     * Get formatted response
     */
    public function getFormattedResponseAttribute(): string
    {
        if (is_array($this->response)) {
            return json_encode($this->response, JSON_PRETTY_PRINT);
        }
        
        return $this->response ?? '';
    }

    /**
     * Check if SMS was successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'sent';
    }

    /**
     * Check if SMS failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}