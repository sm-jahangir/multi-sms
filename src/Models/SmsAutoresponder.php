<?php

namespace MultiSms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SmsAutoresponder extends Model
{
    use HasFactory;

    protected $table = 'sms_autoresponders';

    protected $fillable = [
        'name',
        'trigger_type',
        'trigger_value',
        'response_message',
        'template_id',
        'is_active',
        'delay_minutes',
        'max_triggers_per_number',
        'conditions',
        'settings'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'delay_minutes' => 'integer',
        'max_triggers_per_number' => 'integer',
        'conditions' => 'array',
        'settings' => 'array'
    ];

    const TRIGGER_KEYWORD = 'keyword';
    const TRIGGER_INCOMING_SMS = 'incoming_sms';
    const TRIGGER_MISSED_CALL = 'missed_call';
    const TRIGGER_WEBHOOK = 'webhook';

    /**
     * Get the template used for this autoresponder
     */
    public function template()
    {
        return $this->belongsTo(SmsTemplate::class);
    }

    /**
     * Get triggers for this autoresponder
     */
    public function triggers()
    {
        return $this->hasMany(SmsTrigger::class, 'autoresponder_id');
    }

    /**
     * Get automation logs for this autoresponder
     */
    public function automationLogs()
    {
        return $this->hasMany(SmsAutomationLog::class, 'autoresponder_id');
    }

    /**
     * Scope for active autoresponders
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific trigger type
     */
    public function scopeByTriggerType($query, string $triggerType)
    {
        return $query->where('trigger_type', $triggerType);
    }

    /**
     * Check if autoresponder matches trigger
     */
    public function matchesTrigger(string $triggerType, string $triggerValue, array $context = []): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->trigger_type !== $triggerType) {
            return false;
        }

        return match ($triggerType) {
            self::TRIGGER_KEYWORD => $this->matchesKeyword($triggerValue),
            self::TRIGGER_INCOMING_SMS => $this->matchesIncomingSms($triggerValue, $context),
            self::TRIGGER_MISSED_CALL => $this->matchesMissedCall($triggerValue, $context),
            self::TRIGGER_WEBHOOK => $this->matchesWebhook($triggerValue, $context),
            default => false
        };
    }

    /**
     * Check if keyword matches
     */
    protected function matchesKeyword(string $message): bool
    {
        $keywords = is_array($this->trigger_value) ? $this->trigger_value : [$this->trigger_value];
        $message = strtolower(trim($message));

        foreach ($keywords as $keyword) {
            if (strtolower(trim($keyword)) === $message) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if incoming SMS matches
     */
    protected function matchesIncomingSms(string $message, array $context): bool
    {
        // Check conditions if any
        if (!empty($this->conditions)) {
            return $this->evaluateConditions($context);
        }

        // Default: match any incoming SMS
        return true;
    }

    /**
     * Check if missed call matches
     */
    protected function matchesMissedCall(string $phoneNumber, array $context): bool
    {
        // Check conditions if any
        if (!empty($this->conditions)) {
            return $this->evaluateConditions($context);
        }

        // Default: match any missed call
        return true;
    }

    /**
     * Check if webhook matches
     */
    protected function matchesWebhook(string $webhookData, array $context): bool
    {
        // Check conditions if any
        if (!empty($this->conditions)) {
            return $this->evaluateConditions($context);
        }

        // Default: match any webhook
        return true;
    }

    /**
     * Evaluate conditions
     */
    protected function evaluateConditions(array $context): bool
    {
        foreach ($this->conditions as $condition) {
            $field = $condition['field'] ?? '';
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'] ?? '';
            $contextValue = $context[$field] ?? null;

            $result = match ($operator) {
                '=' => $contextValue == $value,
                '!=' => $contextValue != $value,
                '>' => $contextValue > $value,
                '<' => $contextValue < $value,
                '>=' => $contextValue >= $value,
                '<=' => $contextValue <= $value,
                'contains' => str_contains(strtolower($contextValue), strtolower($value)),
                'starts_with' => str_starts_with(strtolower($contextValue), strtolower($value)),
                'ends_with' => str_ends_with(strtolower($contextValue), strtolower($value)),
                default => false
            };

            if (!$result) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if autoresponder can be triggered for phone number
     */
    public function canTriggerForNumber(string $phoneNumber): bool
    {
        if ($this->max_triggers_per_number === 0) {
            return true; // No limit
        }

        $triggerCount = $this->automationLogs()
            ->where('phone_number', $phoneNumber)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        return $triggerCount < $this->max_triggers_per_number;
    }

    /**
     * Get response message with variables
     */
    public function getResponseMessage(array $variables = []): string
    {
        if ($this->template) {
            return $this->template->render($variables);
        }

        $message = $this->response_message;
        
        foreach ($variables as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
            $message = str_replace('{{' . $key . '}}', $value, $message);
        }
        
        return $message;
    }

    /**
     * Get trigger count for today
     */
    public function getTodayTriggerCountAttribute(): int
    {
        return $this->automationLogs()
            ->whereDate('created_at', today())
            ->count();
    }

    /**
     * Get total trigger count
     */
    public function getTotalTriggerCountAttribute(): int
    {
        return $this->automationLogs()->count();
    }
}