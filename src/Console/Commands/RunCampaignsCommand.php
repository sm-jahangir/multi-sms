<?php

namespace MultiSms\Console\Commands;

use Illuminate\Console\Command;
use MultiSms\Models\SmsCampaign;
use MultiSms\Services\SmsService;
use MultiSms\Exceptions\SmsException;
use Carbon\Carbon;

class RunCampaignsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sms:run-campaigns 
                            {--campaign= : Specific campaign ID to run}
                            {--force : Force run campaigns even if not scheduled}
                            {--dry-run : Show what would be processed without actually sending}
                            {--limit= : Maximum number of campaigns to process (default: 10)}';

    /**
     * The console command description.
     */
    protected $description = 'Run scheduled SMS campaigns';

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
            $campaignId = $this->option('campaign');
            $force = $this->option('force');
            $dryRun = $this->option('dry-run');
            $limit = (int) ($this->option('limit') ?? 10);

            $this->info('Starting SMS campaign processor...');
            $this->newLine();

            // Process specific campaign if ID provided
            if ($campaignId) {
                return $this->processCampaign($campaignId, $force, $dryRun);
            }

            // Process scheduled campaigns
            return $this->processScheduledCampaigns($force, $dryRun, $limit);

        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Process a specific campaign
     */
    protected function processCampaign(string $campaignId, bool $force, bool $dryRun): int
    {
        $campaign = SmsCampaign::find($campaignId);
        
        if (!$campaign) {
            $this->error("Campaign with ID {$campaignId} not found");
            return self::FAILURE;
        }

        $this->info("Processing campaign: {$campaign->name} (ID: {$campaign->id})");
        
        // Check if campaign can be processed
        if (!$force && !$this->canProcessCampaign($campaign)) {
            $this->warn("Campaign cannot be processed. Status: {$campaign->status}");
            return self::FAILURE;
        }

        if ($dryRun) {
            $this->showCampaignPreview($campaign);
            return self::SUCCESS;
        }

        return $this->executeCampaign($campaign);
    }

    /**
     * Process all scheduled campaigns
     */
    protected function processScheduledCampaigns(bool $force, bool $dryRun, int $limit): int
    {
        $query = SmsCampaign::query();

        if ($force) {
            // Include draft and scheduled campaigns
            $query->whereIn('status', ['draft', 'scheduled']);
        } else {
            // Only scheduled campaigns that are due
            $query->where('status', 'scheduled')
                  ->where('scheduled_at', '<=', Carbon::now());
        }

        $campaigns = $query->orderBy('scheduled_at')
                          ->limit($limit)
                          ->get();

        if ($campaigns->isEmpty()) {
            $this->info('No campaigns to process');
            return self::SUCCESS;
        }

        $this->info("Found {$campaigns->count()} campaign(s) to process");
        $this->newLine();

        $processedCount = 0;
        $successCount = 0;
        $failedCount = 0;

        foreach ($campaigns as $campaign) {
            $this->line("Processing: {$campaign->name} (ID: {$campaign->id})");
            
            if ($dryRun) {
                $this->showCampaignPreview($campaign);
                $processedCount++;
                continue;
            }

            try {
                $result = $this->executeCampaign($campaign);
                if ($result === self::SUCCESS) {
                    $successCount++;
                } else {
                    $failedCount++;
                }
                $processedCount++;
                
            } catch (\Exception $e) {
                $this->error("Failed to process campaign {$campaign->id}: {$e->getMessage()}");
                $failedCount++;
                $processedCount++;
            }

            $this->newLine();
        }

        // Show summary
        $this->info('Campaign processing completed:');
        $this->line("Total processed: {$processedCount}");
        if (!$dryRun) {
            $this->line("Successful: {$successCount}");
            $this->line("Failed: {$failedCount}");
        }

        return $failedCount === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Check if campaign can be processed
     */
    protected function canProcessCampaign(SmsCampaign $campaign): bool
    {
        return in_array($campaign->status, ['draft', 'scheduled']);
    }

    /**
     * Show campaign preview
     */
    protected function showCampaignPreview(SmsCampaign $campaign): void
    {
        $this->line("Campaign: {$campaign->name}");
        $this->line("Status: {$campaign->status}");
        $this->line("Recipients: {$campaign->total_recipients}");
        $this->line("Scheduled: " . ($campaign->scheduled_at ? $campaign->scheduled_at->format('Y-m-d H:i:s') : 'Not scheduled'));
        
        if ($campaign->template_id && $campaign->template) {
            $this->line("Template: {$campaign->template->name} ({$campaign->template->key})");
            $message = $campaign->template->body;
        } else {
            $message = $campaign->message;
        }
        
        $this->line("Message: " . substr($message, 0, 100) . (strlen($message) > 100 ? '...' : ''));
        $this->line("Driver: " . ($campaign->driver ?? 'Default'));
        $this->line("From: " . ($campaign->from_number ?? 'Default'));
        
        if ($campaign->recipients && is_array($campaign->recipients)) {
            $this->line("Sample recipients: " . implode(', ', array_slice($campaign->recipients, 0, 3)) . 
                       (count($campaign->recipients) > 3 ? '...' : ''));
        }
    }

    /**
     * Execute campaign
     */
    protected function executeCampaign(SmsCampaign $campaign): int
    {
        try {
            // Start the campaign
            if ($campaign->status !== 'running') {
                $campaign->start();
                $this->info("Campaign started: {$campaign->name}");
            }

            // Get message
            $message = $campaign->message;
            if ($campaign->template_id && $campaign->template) {
                $message = $campaign->template->body;
            }

            if (!$message) {
                throw new SmsException('No message content found for campaign');
            }

            // Send bulk SMS
            $this->line('Sending SMS to recipients...');
            
            $results = $this->smsService->sendBulk(
                $campaign->recipients,
                $message,
                $campaign->driver,
                $campaign->from_number,
                $campaign->template_id,
                [], // variables
                50  // batch size
            );

            // Update campaign statistics
            $successCount = collect($results)->where('success', true)->count();
            $failedCount = collect($results)->where('success', false)->count();

            $campaign->update([
                'sent_count' => $successCount,
                'failed_count' => $failedCount
            ]);

            // Complete the campaign
            $campaign->complete();

            $this->info("Campaign completed successfully!");
            $this->line("Total sent: {$successCount}");
            $this->line("Failed: {$failedCount}");
            $this->line("Success rate: " . ($campaign->total_recipients > 0 ? 
                round(($successCount / $campaign->total_recipients) * 100, 2) : 0) . '%');

            return self::SUCCESS;

        } catch (SmsException $e) {
            $campaign->fail();
            $this->error("Campaign failed: {$e->getMessage()}");
            if ($e->getContext()) {
                $this->line('Context: ' . json_encode($e->getContext(), JSON_PRETTY_PRINT));
            }
            return self::FAILURE;

        } catch (\Exception $e) {
            $campaign->fail();
            $this->error("Campaign failed with unexpected error: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}