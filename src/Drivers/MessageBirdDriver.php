<?php

namespace MultiSms\Drivers;

use MultiSms\Exceptions\SmsException;

class MessageBirdDriver extends AbstractDriver
{
    protected string $baseUrl = 'https://rest.messagebird.com';

    public function send(string $to, string $message, string $from): array
    {
        $this->validateConfig(['access_key']);
        
        $to = $this->validatePhoneNumber($to);
        $from = $from ?: $this->getConfig('from');
        
        if (empty($from)) {
            throw new SmsException('From number is required for MessageBird driver');
        }

        $url = "{$this->baseUrl}/messages";
        
        $response = $this->makeRequest('POST', $url, [
            'form_params' => [
                'originator' => $from,
                'recipients' => ltrim($to, '+'),
                'body' => $message
            ],
            'headers' => [
                'Authorization' => 'AccessKey ' . $this->getConfig('access_key'),
                'Content-Type' => 'application/x-www-form-urlencoded'
            ]
        ]);

        if ($response['status_code'] !== 201) {
            $errorMessage = 'MessageBird API error: HTTP ' . $response['status_code'];
            if (isset($response['data']['errors'])) {
                $errors = [];
                foreach ($response['data']['errors'] as $error) {
                    $errors[] = $error['description'] ?? $error['code'] ?? 'Unknown error';
                }
                $errorMessage .= ' - ' . implode(', ', $errors);
            }
            throw new SmsException($errorMessage);
        }

        $data = $response['data'];

        return [
            'message_id' => $data['id'] ?? null,
            'href' => $data['href'] ?? null,
            'direction' => $data['direction'] ?? null,
            'type' => $data['type'] ?? null,
            'originator' => $data['originator'] ?? null,
            'body' => $data['body'] ?? null,
            'reference' => $data['reference'] ?? null,
            'validity' => $data['validity'] ?? null,
            'gateway' => $data['gateway'] ?? null,
            'datacoding' => $data['datacoding'] ?? null,
            'mclass' => $data['mclass'] ?? null,
            'scheduledDatetime' => $data['scheduledDatetime'] ?? null,
            'createdDatetime' => $data['createdDatetime'] ?? null,
            'recipients' => $data['recipients'] ?? null,
            'raw_response' => $data
        ];
    }

    public function getName(): string
    {
        return 'messagebird';
    }

    public function isConfigured(): bool
    {
        return !empty($this->getConfig('access_key'));
    }
}