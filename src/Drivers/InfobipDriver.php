<?php

namespace MultiSms\Drivers;

use MultiSms\Exceptions\SmsException;

class InfobipDriver extends AbstractDriver
{
    public function send(string $to, string $message, string $from): array
    {
        $this->validateConfig(['api_key']);
        
        $to = $this->validatePhoneNumber($to);
        $from = $from ?: $this->getConfig('from');
        
        if (empty($from)) {
            throw new SmsException('From number is required for Infobip driver');
        }

        $baseUrl = $this->getConfig('base_url', 'https://api.infobip.com');
        $url = "{$baseUrl}/sms/2/text/advanced";
        
        $response = $this->makeRequest('POST', $url, [
            'json' => [
                'messages' => [
                    [
                        'from' => $from,
                        'destinations' => [
                            ['to' => ltrim($to, '+')]
                        ],
                        'text' => $message
                    ]
                ]
            ],
            'headers' => [
                'Authorization' => 'App ' . $this->getConfig('api_key'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]
        ]);

        if ($response['status_code'] !== 200) {
            $errorMessage = 'Infobip API error: HTTP ' . $response['status_code'];
            if (isset($response['data']['requestError'])) {
                $errorMessage .= ' - ' . $response['data']['requestError']['text'];
            }
            throw new SmsException($errorMessage);
        }

        $data = $response['data'];
        
        if (!isset($data['messages']) || empty($data['messages'])) {
            throw new SmsException('Infobip API error: No messages in response');
        }

        $messageData = $data['messages'][0];
        
        if (isset($messageData['status']['groupId']) && $messageData['status']['groupId'] !== 1) {
            throw new SmsException(
                'Infobip API error: ' . ($messageData['status']['description'] ?? 'Unknown error')
            );
        }

        return [
            'message_id' => $messageData['messageId'] ?? null,
            'status' => $messageData['status'] ?? null,
            'to' => $messageData['to'] ?? $to,
            'sms_count' => $messageData['smsCount'] ?? 1,
            'raw_response' => $data
        ];
    }

    public function getName(): string
    {
        return 'infobip';
    }

    public function isConfigured(): bool
    {
        return !empty($this->getConfig('api_key'));
    }
}