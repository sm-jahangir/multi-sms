<?php

namespace MultiSms\Drivers;

use MultiSms\Exceptions\SmsException;

class WhatsAppDriver extends AbstractDriver
{
    protected string $baseUrl = 'https://graph.facebook.com/v18.0';

    public function send(string $to, string $message, string $from): array
    {
        $this->validateConfig(['access_token', 'phone_number_id']);
        
        $to = $this->validatePhoneNumber($to);
        $phoneNumberId = $this->getConfig('phone_number_id');
        
        $url = "{$this->baseUrl}/{$phoneNumberId}/messages";
        
        $response = $this->makeRequest('POST', $url, [
            'json' => [
                'messaging_product' => 'whatsapp',
                'to' => ltrim($to, '+'),
                'type' => 'text',
                'text' => [
                    'body' => $message
                ]
            ],
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getConfig('access_token'),
                'Content-Type' => 'application/json'
            ]
        ]);

        if ($response['status_code'] !== 200) {
            $errorMessage = 'WhatsApp API error: HTTP ' . $response['status_code'];
            if (isset($response['data']['error'])) {
                $error = $response['data']['error'];
                $errorMessage .= ' - ' . ($error['message'] ?? $error['type'] ?? 'Unknown error');
            }
            throw new SmsException($errorMessage);
        }

        $data = $response['data'];
        
        if (!isset($data['messages']) || empty($data['messages'])) {
            throw new SmsException('WhatsApp API error: No messages in response');
        }

        $messageData = $data['messages'][0];

        return [
            'message_id' => $messageData['id'] ?? null,
            'messaging_product' => $data['messaging_product'] ?? 'whatsapp',
            'contacts' => $data['contacts'] ?? [],
            'raw_response' => $data
        ];
    }

    public function getName(): string
    {
        return 'whatsapp';
    }

    public function isConfigured(): bool
    {
        return !empty($this->getConfig('access_token')) && 
               !empty($this->getConfig('phone_number_id'));
    }
}