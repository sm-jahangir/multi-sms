<?php

namespace MultiSms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class SmsCampaign extends Model
{
    use HasFactory;

    protected $table = 'sms_campaigns';

    protected $fillable = [
        'name',
        'message',
        'recipients',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'template_id',
        'driver',
        'from_number',
        'total_recipients',
        'sent_count',
        'failed_count',
        'settings',
        'created_by'
    ];

    protected $casts = [
        'recipients' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'settings' => 'array',
        'total_recipients' => 'integer',
        'sent_count' => 'integer',
        'failed_count' => 'integer'
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_RUNNING = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the template used for this campaign
     */
    public function template()
    {
        return $this->belongsTo(SmsTemplate::class);
    }

    /**
     * Get SMS logs for this campaign
     */
    public function smsLogs()
    {
        return $this->hasMany(SmsLog::class, 'campaign_id');
    }

    /**
     * Scope for scheduled campaigns
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED)
                    ->where('scheduled_at', '<=', now());
    }

    /**
     * Scope for running campaigns
     */
    public function scopeRunning($query)
    {
        return $query->where('status', self::STATUS_RUNNING);
    }

    /**
     * Scope for completed campaigns
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for draft campaigns
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Check if campaign is ready to run
     */
    public function isReadyToRun(): bool
    {
        return $this->status === self::STATUS_SCHEDULED && 
               $this->scheduled_at <= now();
    }

    /**
     * Check if campaign is running
     */
    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    /**
     * Check if campaign is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Start the campaign
     */
    public function start(): void
    {
        $this->update([
            'status' => self::STATUS_RUNNING,
            'started_at' => now()
        ]);
    }

    /**
     * Complete the campaign
     */
    public function complete(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now()
        ]);
    }

    /**
     * Cancel the campaign
     */
    public function cancel(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED
        ]);
    }

    /**
     * Mark campaign as failed
     */
    public function markAsFailed(): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => now()
        ]);
    }

    /**
     * Get success rate percentage
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_recipients === 0) {
            return 0;
        }
        
        return round(($this->sent_count / $this->total_recipients) * 100, 2);
    }

    /**
     * Get failure rate percentage
     */
    public function getFailureRateAttribute(): float
    {
        if ($this->total_recipients === 0) {
            return 0;
        }
        
        return round(($this->failed_count / $this->total_recipients) * 100, 2);
    }

    /**
     * Get remaining recipients count
     */
    public function getRemainingRecipientsAttribute(): int
    {
        return $this->total_recipients - $this->sent_count - $this->failed_count;
    }

    /**
     * Get campaign duration
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }
        
        return $this->started_at->diffInSeconds($this->completed_at);
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): ?string
    {
        if (!$this->duration) {
            return null;
        }
        
        $hours = floor($this->duration / 3600);
        $minutes = floor(($this->duration % 3600) / 60);
        $seconds = $this->duration % 60;
        
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    /**
     * Increment sent count
     */
    public function incrementSentCount(): void
    {
        $this->increment('sent_count');
    }

    /**
     * Increment failed count
     */
    public function incrementFailedCount(): void
    {
        $this->increment('failed_count');
    }
}