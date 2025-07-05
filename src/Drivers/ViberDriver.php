<?php

namespace MultiSms\Drivers;

use MultiSms\Exceptions\SmsException;

class ViberDriver extends AbstractDriver
{
    protected string $baseUrl = 'https://chatapi.viber.com/pa';

    public function send(string $to, string $message, string $from): array
    {
        $this->validateConfig(['auth_token']);
        
        $to = $this->validatePhoneNumber($to);
        $senderName = $from ?: $this->getConfig('sender_name');
        
        if (empty($senderName)) {
            throw new SmsException('Sender name is required for Viber driver');
        }

        $url = "{$this->baseUrl}/send_message";
        
        $response = $this->makeRequest('POST', $url, [
            'json' => [
                'receiver' => ltrim($to, '+'),
                'min_api_version' => 1,
                'sender' => [
                    'name' => $senderName
                ],
                'tracking_data' => 'tracking data',
                'type' => 'text',
                'text' => $message
            ],
            'headers' => [
                'X-Viber-Auth-Token' => $this->getConfig('auth_token'),
                'Content-Type' => 'application/json'
            ]
        ]);

        if ($response['status_code'] !== 200) {
            throw new SmsException('Viber API error: HTTP ' . $response['status_code']);
        }

        $data = $response['data'];
        
        if ($data['status'] !== 0) {
            throw new SmsException(
                'Viber API error: ' . ($data['status_message'] ?? 'Status: ' . $data['status'])
            );
        }

        return [
            'message_id' => $data['message_token'] ?? null,
            'status' => $data['status'],
            'status_message' => $data['status_message'] ?? null,
            'chat_hostname' => $data['chat_hostname'] ?? null,
            'billing_status' => $data['billing_status'] ?? null,
            'raw_response' => $data
        ];
    }

    public function getName(): string
    {
        return 'viber';
    }

    public function isConfigured(): bool
    {
        return !empty($this->getConfig('auth_token'));
    }
}