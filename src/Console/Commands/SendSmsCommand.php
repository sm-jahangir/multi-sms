<?php

namespace MultiSms\Console\Commands;

use Illuminate\Console\Command;
use MultiSms\Services\SmsService;
use MultiSms\Models\SmsTemplate;
use MultiSms\Exceptions\SmsException;

class SendSmsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sms:send 
                            {to : The recipient phone number}
                            {message? : The SMS message to send}
                            {--driver= : The SMS driver to use}
                            {--from= : The sender phone number}
                            {--template= : Template key to use}
                            {--variables= : JSON string of template variables}
                            {--bulk= : Comma-separated list of phone numbers for bulk sending}
                            {--dry-run : Show what would be sent without actually sending}';

    /**
     * The console command description.
     */
    protected $description = 'Send SMS messages via command line';

    protected SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        parent::__construct();
        $this->smsService = $smsService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $to = $this->argument('to');
            $message = $this->argument('message');
            $driver = $this->option('driver');
            $from = $this->option('from');
            $templateKey = $this->option('template');
            $variablesJson = $this->option('variables');
            $bulkNumbers = $this->option('bulk');
            $dryRun = $this->option('dry-run');

            // Parse variables if provided
            $variables = [];
            if ($variablesJson) {
                $variables = json_decode($variablesJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->error('Invalid JSON format for variables');
                    return self::FAILURE;
                }
            }

            // Get template if specified
            $template = null;
            $templateId = null;
            if ($templateKey) {
                $template = SmsTemplate::byKey($templateKey)->active()->first();
                if (!$template) {
                    $this->error("Template with key '{$templateKey}' not found or inactive");
                    return self::FAILURE;
                }
                $templateId = $template->id;
                
                // Use template body if no message provided
                if (!$message) {
                    $message = $template->render($variables);
                }
            }

            // Validate that we have a message
            if (!$message) {
                $this->error('Either message or template must be provided');
                return self::FAILURE;
            }

            // Handle bulk sending
            if ($bulkNumbers) {
                return $this->handleBulkSending($bulkNumbers, $message, $driver, $from, $templateId, $variables, $dryRun);
            }

            // Handle single SMS
            return $this->handleSingleSms($to, $message, $driver, $from, $templateId, $variables, $dryRun, $template);

        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Handle sending a single SMS
     */
    protected function handleSingleSms(
        string $to,
        string $message,
        ?string $driver,
        ?string $from,
        ?int $templateId,
        array $variables,
        bool $dryRun,
        ?SmsTemplate $template
    ): int {
        // Show preview
        $this->info('SMS Preview:');
        $this->line('To: ' . $to);
        $this->line('From: ' . ($from ?? 'Default'));
        $this->line('Driver: ' . ($driver ?? 'Default'));
        if ($template) {
            $this->line('Template: ' . $template->name . ' (' . $template->key . ')');
            if (!empty($variables)) {
                $this->line('Variables: ' . json_encode($variables, JSON_PRETTY_PRINT));
            }
        }
        $this->line('Message: ' . $message);
        $this->line('Length: ' . strlen($message) . ' characters');
        $this->newLine();

        if ($dryRun) {
            $this->info('Dry run mode - SMS not sent');
            return self::SUCCESS;
        }

        // Confirm sending
        if (!$this->confirm('Do you want to send this SMS?')) {
            $this->info('SMS sending cancelled');
            return self::SUCCESS;
        }

        // Send SMS
        try {
            $this->info('Sending SMS...');
            
            $result = $this->smsService->send(
                $to,
                $message,
                $driver,
                $from,
                $templateId,
                $variables
            );

            $this->info('SMS sent successfully!');
            $this->line('Message ID: ' . $result['message_id']);
            $this->line('Driver used: ' . $result['driver']);
            if (isset($result['cost'])) {
                $this->line('Cost: $' . number_format($result['cost'], 4));
            }

            return self::SUCCESS;

        } catch (SmsException $e) {
            $this->error('SMS sending failed: ' . $e->getMessage());
            if ($e->getContext()) {
                $this->line('Context: ' . json_encode($e->getContext(), JSON_PRETTY_PRINT));
            }
            return self::FAILURE;
        }
    }

    /**
     * Handle bulk SMS sending
     */
    protected function handleBulkSending(
        string $bulkNumbers,
        string $message,
        ?string $driver,
        ?string $from,
        ?int $templateId,
        array $variables,
        bool $dryRun
    ): int {
        // Parse phone numbers
        $phoneNumbers = array_map('trim', explode(',', $bulkNumbers));
        $phoneNumbers = array_filter($phoneNumbers); // Remove empty values

        if (empty($phoneNumbers)) {
            $this->error('No valid phone numbers provided for bulk sending');
            return self::FAILURE;
        }

        // Show preview
        $this->info('Bulk SMS Preview:');
        $this->line('Recipients: ' . count($phoneNumbers));
        $this->line('Phone numbers: ' . implode(', ', $phoneNumbers));
        $this->line('From: ' . ($from ?? 'Default'));
        $this->line('Driver: ' . ($driver ?? 'Default'));
        $this->line('Message: ' . $message);
        $this->line('Length: ' . strlen($message) . ' characters');
        $this->newLine();

        if ($dryRun) {
            $this->info('Dry run mode - SMS not sent');
            return self::SUCCESS;
        }

        // Confirm sending
        if (!$this->confirm('Do you want to send SMS to ' . count($phoneNumbers) . ' recipients?')) {
            $this->info('Bulk SMS sending cancelled');
            return self::SUCCESS;
        }

        // Send bulk SMS
        try {
            $this->info('Sending bulk SMS...');
            
            $results = $this->smsService->sendBulk(
                $phoneNumbers,
                $message,
                $driver,
                $from,
                $templateId,
                $variables,
                50 // batch size
            );

            // Show results
            $successCount = collect($results)->where('success', true)->count();
            $failedCount = collect($results)->where('success', false)->count();

            $this->info('Bulk SMS sending completed!');
            $this->line('Total: ' . count($results));
            $this->line('Successful: ' . $successCount);
            $this->line('Failed: ' . $failedCount);

            // Show failed sends if any
            if ($failedCount > 0) {
                $this->newLine();
                $this->warn('Failed sends:');
                foreach ($results as $result) {
                    if (!$result['success']) {
                        $this->line('- ' . $result['to'] . ': ' . $result['error']);
                    }
                }
            }

            return $successCount > 0 ? self::SUCCESS : self::FAILURE;

        } catch (SmsException $e) {
            $this->error('Bulk SMS sending failed: ' . $e->getMessage());
            if ($e->getContext()) {
                $this->line('Context: ' . json_encode($e->getContext(), JSON_PRETTY_PRINT));
            }
            return self::FAILURE;
        }
    }
}