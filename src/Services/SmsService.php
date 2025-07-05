<?php

namespace MultiSms\Services;

use MultiSms\Models\SmsLog;
use MultiSms\Drivers\DriverInterface;
use MultiSms\Drivers\TwilioDriver;
use MultiSms\Drivers\VonageDriver;
use MultiSms\Drivers\PlivoDriver;
use MultiSms\Drivers\InfobipDriver;
use MultiSms\Drivers\MessageBirdDriver;
use MultiSms\Drivers\ViberDriver;
use MultiSms\Drivers\WhatsAppDriver;
use MultiSms\Exceptions\SmsException;
use Illuminate\Support\Facades\Log;
use Exception;

class SmsService
{
    protected array $config;
    protected array $drivers = [];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Create a new SMS builder instance for fluent interface
     */
    public function to(string|array $to): SmsBuilder
    {
        return (new SmsBuilder($this))->to($to);
    }

    /**
     * Set driver and return builder instance
     */
    public function driver(string $driver): SmsBuilder
    {
        return (new SmsBuilder($this))->driver($driver);
    }

    /**
     * Send SMS message
     */
    public function send(string $to, string $message, ?string $driver = null, ?string $from = null): array
    {
        $driver = $driver ?? $this->config['default'];
        $from = $from ?? $this->getDefaultFrom($driver);
        
        $drivers = $this->getFallbackDrivers($driver);
        $lastException = null;
        
        foreach ($drivers as $currentDriver) {
            try {
                $result = $this->sendWithDriver($to, $message, $currentDriver, $from);
                
                if ($this->config['logging']['enabled'] && $this->config['logging']['log_success']) {
                    $this->logSms($to, $from, $message, $currentDriver, 'sent', $result);
                }
                
                return [
                    'success' => true,
                    'driver' => $currentDriver,
                    'message_id' => $result['message_id'] ?? null,
                    'response' => $result
                ];
                
            } catch (Exception $e) {
                $lastException = $e;
                
                if ($this->config['logging']['enabled'] && $this->config['logging']['log_failures']) {
                    $this->logSms($to, $from, $message, $currentDriver, 'failed', [
                        'error' => $e->getMessage()
                    ]);
                }
                
                Log::warning("SMS failed with driver {$currentDriver}: " . $e->getMessage());
                continue;
            }
        }
        
        throw new SmsException(
            'All SMS drivers failed. Last error: ' . ($lastException ? $lastException->getMessage() : 'Unknown error')
        );
    }

    /**
     * Send bulk SMS messages
     */
    public function sendBulk(array $recipients, string $message, ?string $driver = null, ?string $from = null): array
    {
        $results = [];
        
        foreach ($recipients as $recipient) {
            try {
                $results[] = $this->send($recipient, $message, $driver, $from);
            } catch (Exception $e) {
                $results[] = [
                    'success' => false,
                    'recipient' => $recipient,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }

    /**
     * Get driver instance
     */
    protected function getDriver(string $driver): DriverInterface
    {
        if (!isset($this->drivers[$driver])) {
            $this->drivers[$driver] = $this->createDriver($driver);
        }
        
        return $this->drivers[$driver];
    }

    /**
     * Create driver instance
     */
    protected function createDriver(string $driver): DriverInterface
    {
        $config = $this->config['drivers'][$driver] ?? [];
        
        return match ($driver) {
            'twilio' => new TwilioDriver($config),
            'vonage' => new VonageDriver($config),
            'plivo' => new PlivoDriver($config),
            'infobip' => new InfobipDriver($config),
            'messagebird' => new MessageBirdDriver($config),
            'viber' => new ViberDriver($config),
            'whatsapp' => new WhatsAppDriver($config),
            default => throw new SmsException("Unsupported SMS driver: {$driver}")
        };
    }

    /**
     * Send SMS with specific driver
     */
    protected function sendWithDriver(string $to, string $message, string $driver, string $from): array
    {
        $driverInstance = $this->getDriver($driver);
        return $driverInstance->send($to, $message, $from);
    }

    /**
     * Get fallback drivers list
     */
    protected function getFallbackDrivers(string $primaryDriver): array
    {
        $fallbackPriority = $this->config['fallback_priority'] ?? [];
        
        // Start with primary driver
        $drivers = [$primaryDriver];
        
        // Add fallback drivers (excluding primary)
        foreach ($fallbackPriority as $driver) {
            if ($driver !== $primaryDriver && !in_array($driver, $drivers)) {
                $drivers[] = $driver;
            }
        }
        
        return $drivers;
    }

    /**
     * Get default from number for driver
     */
    protected function getDefaultFrom(string $driver): string
    {
        return $this->config['drivers'][$driver]['from'] 
            ?? $this->config['default_from'] 
            ?? '';
    }

    /**
     * Log SMS message
     */
    protected function logSms(string $to, string $from, string $message, string $driver, string $status, array $response): void
    {
        try {
            SmsLog::create([
                'to' => $to,
                'from' => $from,
                'body' => $message,
                'driver' => $driver,
                'status' => $status,
                'response' => json_encode($response),
                'sent_at' => now(),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to log SMS: ' . $e->getMessage());
        }
    }

    /**
     * Get available drivers
     */
    public function getAvailableDrivers(): array
    {
        return array_keys($this->config['drivers'] ?? []);
    }

    /**
     * Check if driver is configured
     */
    public function isDriverConfigured(string $driver): bool
    {
        return isset($this->config['drivers'][$driver]);
    }
}