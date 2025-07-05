<?php

namespace MultiSms\Drivers;

use MultiSms\Exceptions\SmsException;

class PlivoDriver extends AbstractDriver
{
    protected string $baseUrl = 'https://api.plivo.com/v1/Account';

    public function send(string $to, string $message, string $from): array
    {
        $this->validateConfig(['auth_id', 'auth_token']);
        
        $to = $this->validatePhoneNumber($to);
        $from = $from ?: $this->getConfig('from');
        
        if (empty($from)) {
            throw new SmsException('From number is required for Plivo driver');
        }

        $url = "{$this->baseUrl}/{$this->getConfig('auth_id')}/Message/";
        
        $response = $this->makeRequest('POST', $url, [
            'auth' => [
                $this->getConfig('auth_id'),
                $this->getConfig('auth_token')
            ],
            'json' => [
                'src' => $from,
                'dst' => ltrim($to, '+'),
                'text' => $message
            ],
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);

        if ($response['status_code'] !== 202) {
            $errorMessage = 'Plivo API error: HTTP ' . $response['status_code'];
            if (isset($response['data']['error'])) {
                $errorMessage .= ' - ' . $response['data']['error'];
            }
            throw new SmsException($errorMessage);
        }

        $data = $response['data'];

        return [
            'message_id' => $data['message_uuid'][0] ?? null,
            'api_id' => $data['api_id'] ?? null,
            'message_uuids' => $data['message_uuid'] ?? [],
            'raw_response' => $data
        ];
    }

    public function getName(): string
    {
        return 'plivo';
    }

    public function isConfigured(): bool
    {
        return !empty($this->getConfig('auth_id')) && 
               !empty($this->getConfig('auth_token'));
    }
}