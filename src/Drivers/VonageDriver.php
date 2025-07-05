<?php

namespace MultiSms\Drivers;

use MultiSms\Exceptions\SmsException;

class VonageDriver extends AbstractDriver
{
    protected string $baseUrl = 'https://rest.nexmo.com';

    public function send(string $to, string $message, string $from): array
    {
        $this->validateConfig(['api_key', 'api_secret']);
        
        $to = $this->validatePhoneNumber($to);
        $from = $from ?: $this->getConfig('from');
        
        if (empty($from)) {
            throw new SmsException('From number is required for Vonage driver');
        }

        $url = "{$this->baseUrl}/sms/json";
        
        $response = $this->makeRequest('POST', $url, [
            'form_params' => [
                'api_key' => $this->getConfig('api_key'),
                'api_secret' => $this->getConfig('api_secret'),
                'to' => ltrim($to, '+'),
                'from' => $from,
                'text' => $message
            ]
        ]);

        if ($response['status_code'] !== 200) {
            throw new SmsException('Vonage API error: HTTP ' . $response['status_code']);
        }

        $data = $response['data'];
        
        if (!isset($data['messages']) || empty($data['messages'])) {
            throw new SmsException('Vonage API error: No messages in response');
        }

        $messageData = $data['messages'][0];
        
        if ($messageData['status'] !== '0') {
            throw new SmsException(
                'Vonage API error: ' . ($messageData['error-text'] ?? 'Status: ' . $messageData['status'])
            );
        }

        return [
            'message_id' => $messageData['message-id'],
            'status' => $messageData['status'],
            'to' => $messageData['to'],
            'remaining_balance' => $messageData['remaining-balance'],
            'message_price' => $messageData['message-price'],
            'network' => $messageData['network'] ?? null,
            'raw_response' => $data
        ];
    }

    public function getName(): string
    {
        return 'vonage';
    }

    public function isConfigured(): bool
    {
        return !empty($this->getConfig('api_key')) && 
               !empty($this->getConfig('api_secret'));
    }
}