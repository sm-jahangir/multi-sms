<?php

namespace MultiSms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class SmsTemplate extends Model
{
    use HasFactory;

    protected $table = 'sms_templates';

    protected $fillable = [
        'key',
        'name',
        'body',
        'tags',
        'variables',
        'is_active',
        'description'
    ];

    protected $casts = [
        'tags' => 'array',
        'variables' => 'array',
        'is_active' => 'boolean'
    ];

    /**
     * Get SMS logs that used this template
     */
    public function smsLogs()
    {
        return $this->hasMany(SmsLog::class, 'template_id');
    }

    /**
     * Get campaigns that use this template
     */
    public function campaigns()
    {
        return $this->hasMany(SmsCampaign::class, 'template_id');
    }

    /**
     * Scope for active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for templates with specific tag
     */
    public function scopeWithTag($query, string $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    /**
     * Scope for templates by key
     */
    public function scopeByKey($query, string $key)
    {
        return $query->where('key', $key);
    }

    /**
     * Render template with variables
     */
    public function render(array $variables = []): string
    {
        $body = $this->body;
        
        foreach ($variables as $key => $value) {
            $body = str_replace('{' . $key . '}', $value, $body);
            $body = str_replace('{{' . $key . '}}', $value, $body);
        }
        
        return $body;
    }

    /**
     * Extract variables from template body
     */
    public function extractVariables(): array
    {
        $variables = [];
        
        // Match {variable} and {{variable}} patterns
        preg_match_all('/\{\{?([a-zA-Z_][a-zA-Z0-9_]*)\}?\}/', $this->body, $matches);
        
        if (!empty($matches[1])) {
            $variables = array_unique($matches[1]);
        }
        
        return $variables;
    }

    /**
     * Validate template variables
     */
    public function validateVariables(array $variables): array
    {
        $requiredVariables = $this->extractVariables();
        $missingVariables = [];
        
        foreach ($requiredVariables as $variable) {
            if (!array_key_exists($variable, $variables)) {
                $missingVariables[] = $variable;
            }
        }
        
        return $missingVariables;
    }

    /**
     * Get template preview with sample data
     */
    public function getPreview(): string
    {
        $sampleVariables = [];
        $extractedVariables = $this->extractVariables();
        
        foreach ($extractedVariables as $variable) {
            $sampleVariables[$variable] = '[' . strtoupper($variable) . ']';
        }
        
        return $this->render($sampleVariables);
    }

    /**
     * Generate unique key if not provided
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($template) {
            if (empty($template->key)) {
                $template->key = Str::slug($template->name) . '_' . time();
            }
        });
    }

    /**
     * Get character count
     */
    public function getCharacterCountAttribute(): int
    {
        return strlen($this->body);
    }

    /**
     * Estimate SMS count (160 chars per SMS)
     */
    public function getEstimatedSmsCountAttribute(): int
    {
        return ceil($this->character_count / 160);
    }
}