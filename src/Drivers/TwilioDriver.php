<?php

namespace MultiSms\Drivers;

use MultiSms\Exceptions\SmsException;

class TwilioDriver extends AbstractDriver
{
    protected string $baseUrl = 'https://api.twilio.com/2010-04-01';

    public function send(string $to, string $message, string $from): array
    {
        $this->validateConfig(['account_sid', 'auth_token']);
        
        $to = $this->validatePhoneNumber($to);
        $from = $from ?: $this->getConfig('from');
        
        if (empty($from)) {
            throw new SmsException('From number is required for Twilio driver');
        }

        $url = "{$this->baseUrl}/Accounts/{$this->getConfig('account_sid')}/Messages.json";
        
        $response = $this->makeRequest('POST', $url, [
            'auth' => [
                $this->getConfig('account_sid'),
                $this->getConfig('auth_token')
            ],
            'form_params' => [
                'To' => $to,
                'From' => $from,
                'Body' => $message
            ]
        ]);

        if ($response['status_code'] !== 201) {
            throw new SmsException(
                'Twilio API error: ' . ($response['data']['message'] ?? 'Unknown error')
            );
        }

        return [
            'message_id' => $response['data']['sid'],
            'status' => $response['data']['status'],
            'to' => $response['data']['to'],
            'from' => $response['data']['from'],
            'price' => $response['data']['price'],
            'price_unit' => $response['data']['price_unit'],
            'raw_response' => $response['data']
        ];
    }

    public function getName(): string
    {
        return 'twilio';
    }

    public function isConfigured(): bool
    {
        return !empty($this->getConfig('account_sid')) && 
               !empty($this->getConfig('auth_token'));
    }
}