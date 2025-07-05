<?php

namespace MultiSms\Services;

use MultiSms\Exceptions\SmsException;

class SmsBuilder
{
    protected SmsService $smsService;
    protected ?string $to = null;
    protected ?string $message = null;
    protected ?string $driver = null;
    protected ?string $from = null;
    protected array $recipients = [];

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Set the recipient phone number or array of recipients
     */
    public function to(string|array $to): self
    {
        if (is_array($to)) {
            $this->recipients = $to;
            $this->to = null;
        } else {
            $this->to = $to;
            $this->recipients = [];
        }
        
        return $this;
    }

    /**
     * Set the SMS message
     */
    public function message(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Set the SMS driver
     */
    public function driver(string $driver): self
    {
        $this->driver = $driver;
        return $this;
    }

    /**
     * Set the sender phone number
     */
    public function from(string $from): self
    {
        $this->from = $from;
        return $this;
    }

    /**
     * Send the SMS message
     */
    public function send(): array
    {
        if (empty($this->message)) {
            throw new SmsException('Message is required');
        }

        // Send to multiple recipients (bulk)
        if (!empty($this->recipients)) {
            return $this->smsService->sendBulk(
                $this->recipients,
                $this->message,
                $this->driver,
                $this->from
            );
        }

        // Send to single recipient
        if (empty($this->to)) {
            throw new SmsException('Recipient is required');
        }

        return $this->smsService->send(
            $this->to,
            $this->message,
            $this->driver,
            $this->from
        );
    }

    /**
     * Reset the builder state
     */
    public function reset(): self
    {
        $this->to = null;
        $this->message = null;
        $this->driver = null;
        $this->from = null;
        $this->recipients = [];
        
        return $this;
    }
}