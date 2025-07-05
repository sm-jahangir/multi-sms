<?php

namespace MultiSms\Drivers;

interface DriverInterface
{
    /**
     * Send SMS message
     *
     * @param string $to Recipient phone number
     * @param string $message SMS message content
     * @param string $from Sender phone number or ID
     * @return array Response data including message_id
     * @throws \MultiSms\Exceptions\SmsException
     */
    public function send(string $to, string $message, string $from): array;

    /**
     * Get driver name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Check if driver is properly configured
     *
     * @return bool
     */
    public function isConfigured(): bool;
}