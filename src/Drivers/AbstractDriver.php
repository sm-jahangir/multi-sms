<?php

namespace MultiSms\Drivers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use MultiSms\Exceptions\SmsException;
use Illuminate\Support\Facades\Log;

abstract class AbstractDriver implements DriverInterface
{
    protected array $config;
    protected Client $httpClient;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->httpClient = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
    }

    /**
     * Make HTTP request
     */
    protected function makeRequest(string $method, string $url, array $options = []): array
    {
        try {
            $response = $this->httpClient->request($method, $url, $options);
            $body = $response->getBody()->getContents();
            
            return [
                'status_code' => $response->getStatusCode(),
                'body' => $body,
                'data' => json_decode($body, true) ?: $body
            ];
        } catch (RequestException $e) {
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 0;
            $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : '';
            
            Log::error("HTTP request failed for {$this->getName()}", [
                'url' => $url,
                'method' => $method,
                'status_code' => $statusCode,
                'response' => $responseBody,
                'error' => $e->getMessage()
            ]);
            
            throw new SmsException(
                "HTTP request failed: {$e->getMessage()}",
                $statusCode,
                $e
            );
        }
    }

    /**
     * Validate phone number format
     */
    protected function validatePhoneNumber(string $phoneNumber): string
    {
        // Remove all non-digit characters except +
        $cleaned = preg_replace('/[^+\d]/', '', $phoneNumber);
        
        // Ensure it starts with + if it doesn't already
        if (!str_starts_with($cleaned, '+')) {
            $cleaned = '+' . $cleaned;
        }
        
        // Basic validation - should be at least 10 digits after +
        if (strlen($cleaned) < 11) {
            throw new SmsException("Invalid phone number format: {$phoneNumber}");
        }
        
        return $cleaned;
    }

    /**
     * Get configuration value
     */
    protected function getConfig(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Check if required config keys are present
     */
    protected function validateConfig(array $requiredKeys): void
    {
        foreach ($requiredKeys as $key) {
            if (empty($this->config[$key])) {
                throw new SmsException("Missing required configuration: {$key} for {$this->getName()} driver");
            }
        }
    }

    /**
     * Generate unique message ID
     */
    protected function generateMessageId(): string
    {
        return uniqid($this->getName() . '_', true);
    }
}