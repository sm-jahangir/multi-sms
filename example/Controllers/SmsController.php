<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use MultiSms\Facades\Sms;
use MultiSms\Models\SmsLog;
use MultiSms\Models\SmsTemplate;
use MultiSms\Models\SmsCampaign;
use MultiSms\Models\SmsAutoresponder;
use Carbon\Carbon;
use Exception;
use Throwable;

/**
 * Main SMS Controller
 * 
 * এই controller এ সব view rendering এবং form handling methods আছে।
 * Frontend views এর জন্য data prepare করে এবং user actions handle করে।
 */
class SmsController extends Controller
{
    /**
     * SMS Dashboard
     * 
     * Main dashboard যেখানে overview statistics, charts এবং quick actions আছে
     */
    public function dashboard(): View
    {
        try {
            // Get overview statistics for last 30 days
            $startDate = now()->subDays(30);
            
            $stats = [
                'total_sent' => SmsLog::where('created_at', '>=', $startDate)->count(),
                'delivered' => SmsLog::where('created_at', '>=', $startDate)
                                    ->where('status', 'delivered')->count(),
                'failed' => SmsLog::where('created_at', '>=', $startDate)
                                 ->where('status', 'failed')->count(),
                'pending' => SmsLog::where('created_at', '>=', $startDate)
                                  ->where('status', 'pending')->count(),
            ];
            
            $stats['success_rate'] = $stats['total_sent'] > 0 
                ? round(($stats['delivered'] / $stats['total_sent']) * 100, 2) 
                : 0;
            
            // Get recent campaigns
            $recentCampaigns = SmsCampaign::with('template')
                                         ->orderBy('created_at', 'desc')
                                         ->limit(5)
                                         ->get();
            
            // Get recent SMS activity
            $recentActivity = SmsLog::with(['template', 'campaign'])
                                   ->orderBy('created_at', 'desc')
                                   ->limit(10)
                                   ->get();
            
            // Get driver statistics
            $driverStats = SmsLog::selectRaw('driver, COUNT(*) as total,
                                            SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered')
                                 ->where('created_at', '>=', $startDate)
                                 ->groupBy('driver')
                                 ->get();
            
            return view('sms.dashboard', compact(
                'stats', 
                'recentCampaigns', 
                'recentActivity', 
                'driverStats'
            ));
            
        } catch (Exception $e) {
            Log::error('Dashboard load failed', ['error' => $e->getMessage()]);
            
            return view('sms.dashboard')->with('error', 'Failed to load dashboard data');
        }
    }

    // ==================== SMS Templates ====================

    /**
     * Templates index page
     */
    public function templatesIndex(): View
    {
        // Get template categories for filter
        $categories = SmsTemplate::distinct('category')
                                 ->whereNotNull('category')
                                 ->pluck('category')
                                 ->filter()
                                 ->sort()
                                 ->values();
        
        return view('sms.templates.index', compact('categories'));
    }

    /**
     * Create/Edit template page
     */
    public function templatesCreate($templateId = null): View
    {
        $template = null;
        $isEdit = false;
        
        if ($templateId) {
            $template = SmsTemplate::findOrFail($templateId);
            $isEdit = true;
        }
        
        // Get categories for dropdown
        $categories = SmsTemplate::distinct('category')
                                 ->whereNotNull('category')
                                 ->pluck('category')
                                 ->filter()
                                 ->sort()
                                 ->values();
        
        return view('sms.templates.create', compact('template', 'isEdit', 'categories'));
    }

    /**
     * Store/Update template
     */
    public function templatesStore(Request $request, $templateId = null): RedirectResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'content' => 'required|string|max:1600',
                'type' => 'required|in:promotional,transactional,otp,notification,reminder',
                'category' => 'nullable|string|max:100',
                'description' => 'nullable|string|max:500',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            $data = $validator->validated();
            $data['is_active'] = $request->has('is_active');
            
            // Extract variables from content
            $data['variables'] = $this->extractVariables($data['content']);
            
            if ($templateId) {
                // Update existing template
                $template = SmsTemplate::findOrFail($templateId);
                $template->update($data);
                $message = 'Template updated successfully!';
            } else {
                // Create new template
                $template = SmsTemplate::create($data);
                $message = 'Template created successfully!';
            }
            
            return redirect()->route('sms.templates.index')
                           ->with('success', $message);
            
        } catch (Exception $e) {
            Log::error('Template save failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);
            
            return back()->with('error', 'Failed to save template: ' . $e->getMessage())
                        ->withInput();
        }
    }

    /**
     * Delete template
     */
    public function templatesDestroy($templateId): RedirectResponse
    {
        try {
            $template = SmsTemplate::findOrFail($templateId);
            $template->delete();
            
            return redirect()->route('sms.templates.index')
                           ->with('success', 'Template deleted successfully!');
            
        } catch (Exception $e) {
            Log::error('Template delete failed', [
                'error' => $e->getMessage(),
                'template_id' => $templateId
            ]);
            
            return back()->with('error', 'Failed to delete template');
        }
    }

    // ==================== SMS Campaigns ====================

    /**
     * Campaigns index page
     */
    public function campaignsIndex(): View
    {
        // Get campaign types and statuses for filters
        $types = SmsCampaign::distinct('type')
                           ->whereNotNull('type')
                           ->pluck('type')
                           ->filter()
                           ->sort()
                           ->values();
        
        $statuses = SmsCampaign::distinct('status')
                              ->whereNotNull('status')
                              ->pluck('status')
                              ->filter()
                              ->sort()
                              ->values();
        
        return view('sms.campaigns.index', compact('types', 'statuses'));
    }

    /**
     * Create/Edit campaign page
     */
    public function campaignsCreate($campaignId = null): View
    {
        $campaign = null;
        $isEdit = false;
        
        if ($campaignId) {
            $campaign = SmsCampaign::with('template')->findOrFail($campaignId);
            $isEdit = true;
        }
        
        // Get templates for dropdown
        $templates = SmsTemplate::where('is_active', true)
                                ->orderBy('name')
                                ->get(['id', 'name', 'content', 'type']);
        
        return view('sms.campaigns.create', compact('campaign', 'isEdit', 'templates'));
    }

    /**
     * Store/Update campaign
     */
    public function campaignsStore(Request $request, $campaignId = null): RedirectResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'type' => 'required|in:bulk,scheduled,drip,triggered',
                'description' => 'nullable|string|max:500',
                'template_id' => 'nullable|exists:sms_templates,id',
                'message_content' => 'required_without:template_id|string|max:1600',
                'sender_id' => 'nullable|string|max:20',
                'recipients' => 'required|string',
                'schedule_type' => 'required|in:immediate,scheduled,recurring',
                'scheduled_at' => 'required_if:schedule_type,scheduled|nullable|date|after:now',
                'recurring_pattern' => 'required_if:schedule_type,recurring|nullable|string',
                'priority' => 'required|in:low,normal,high',
                'rate_limit' => 'nullable|integer|min:1|max:1000',
                'tracking_enabled' => 'boolean'
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            $data = $validator->validated();
            $data['tracking_enabled'] = $request->has('tracking_enabled');
            
            // Process recipients
            $recipients = $this->processRecipients($data['recipients']);
            $data['total_recipients'] = count($recipients);
            $data['recipients_data'] = json_encode($recipients);
            
            // Set status based on schedule type
            $data['status'] = match($data['schedule_type']) {
                'immediate' => 'active',
                'scheduled' => 'scheduled',
                'recurring' => 'scheduled',
                default => 'draft'
            };
            
            if ($campaignId) {
                // Update existing campaign
                $campaign = SmsCampaign::findOrFail($campaignId);
                $campaign->update($data);
                $message = 'Campaign updated successfully!';
            } else {
                // Create new campaign
                $campaign = SmsCampaign::create($data);
                $message = 'Campaign created successfully!';
                
                // If immediate, start sending
                if ($data['schedule_type'] === 'immediate') {
                    $this->startCampaign($campaign);
                }
            }
            
            return redirect()->route('sms.campaigns.index')
                           ->with('success', $message);
            
        } catch (Exception $e) {
            Log::error('Campaign save failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);
            
            return back()->with('error', 'Failed to save campaign: ' . $e->getMessage())
                        ->withInput();
        }
    }

    /**
     * Start campaign
     */
    public function campaignsStart($campaignId): RedirectResponse
    {
        try {
            $campaign = SmsCampaign::findOrFail($campaignId);
            
            if ($campaign->status !== 'draft' && $campaign->status !== 'paused') {
                return back()->with('error', 'Campaign cannot be started in current status');
            }
            
            $this->startCampaign($campaign);
            
            return back()->with('success', 'Campaign started successfully!');
            
        } catch (Exception $e) {
            Log::error('Campaign start failed', [
                'error' => $e->getMessage(),
                'campaign_id' => $campaignId
            ]);
            
            return back()->with('error', 'Failed to start campaign');
        }
    }

    /**
     * Pause campaign
     */
    public function campaignsPause($campaignId): RedirectResponse
    {
        try {
            $campaign = SmsCampaign::findOrFail($campaignId);
            $campaign->update(['status' => 'paused']);
            
            return back()->with('success', 'Campaign paused successfully!');
            
        } catch (Exception $e) {
            return back()->with('error', 'Failed to pause campaign');
        }
    }

    /**
     * Delete campaign
     */
    public function campaignsDestroy($campaignId): RedirectResponse
    {
        try {
            $campaign = SmsCampaign::findOrFail($campaignId);
            $campaign->delete();
            
            return redirect()->route('sms.campaigns.index')
                           ->with('success', 'Campaign deleted successfully!');
            
        } catch (Exception $e) {
            return back()->with('error', 'Failed to delete campaign');
        }
    }

    // ==================== SMS Autoresponders ====================

    /**
     * Autoresponders index page
     */
    public function autorespondersIndex(): View
    {
        // Get trigger types for filter
        $triggerTypes = SmsAutoresponder::distinct('trigger_type')
                                       ->whereNotNull('trigger_type')
                                       ->pluck('trigger_type')
                                       ->filter()
                                       ->sort()
                                       ->values();
        
        return view('sms.autoresponders.index', compact('triggerTypes'));
    }

    /**
     * Create/Edit autoresponder page
     */
    public function autorespondersCreate($autoresponderId = null): View
    {
        $autoresponder = null;
        $isEdit = false;
        
        if ($autoresponderId) {
            $autoresponder = SmsAutoresponder::findOrFail($autoresponderId);
            $isEdit = true;
        }
        
        // Get templates for dropdown
        $templates = SmsTemplate::where('is_active', true)
                                ->orderBy('name')
                                ->get(['id', 'name', 'content']);
        
        return view('sms.autoresponders.create', compact('autoresponder', 'isEdit', 'templates'));
    }

    /**
     * Store/Update autoresponder
     */
    public function autorespondersStore(Request $request, $autoresponderId = null): RedirectResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'trigger_type' => 'required|in:keyword,schedule,event',
                'keyword' => 'required_if:trigger_type,keyword|nullable|string|max:50',
                'schedule_pattern' => 'required_if:trigger_type,schedule|nullable|string',
                'event_type' => 'required_if:trigger_type,event|nullable|string',
                'template_id' => 'nullable|exists:sms_templates,id',
                'response_message' => 'required_without:template_id|string|max:1600',
                'response_delay' => 'nullable|integer|min:0|max:3600',
                'max_triggers_per_day' => 'nullable|integer|min:1|max:100',
                'cooldown_minutes' => 'nullable|integer|min:1|max:1440',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after:start_date',
                'case_sensitive' => 'boolean',
                'exact_match' => 'boolean',
                'is_active' => 'boolean',
                'enable_logging' => 'boolean',
                'description' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            $data = $validator->validated();
            $data['case_sensitive'] = $request->has('case_sensitive');
            $data['exact_match'] = $request->has('exact_match');
            $data['is_active'] = $request->has('is_active');
            $data['enable_logging'] = $request->has('enable_logging');
            
            if ($autoresponderId) {
                // Update existing autoresponder
                $autoresponder = SmsAutoresponder::findOrFail($autoresponderId);
                $autoresponder->update($data);
                $message = 'Autoresponder updated successfully!';
            } else {
                // Create new autoresponder
                $autoresponder = SmsAutoresponder::create($data);
                $message = 'Autoresponder created successfully!';
            }
            
            return redirect()->route('sms.autoresponders.index')
                           ->with('success', $message);
            
        } catch (Exception $e) {
            Log::error('Autoresponder save failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);
            
            return back()->with('error', 'Failed to save autoresponder: ' . $e->getMessage())
                        ->withInput();
        }
    }

    /**
     * Toggle autoresponder status
     */
    public function autorespondersToggle($autoresponderId): RedirectResponse
    {
        try {
            $autoresponder = SmsAutoresponder::findOrFail($autoresponderId);
            $autoresponder->update(['is_active' => !$autoresponder->is_active]);
            
            $status = $autoresponder->is_active ? 'activated' : 'deactivated';
            
            return back()->with('success', "Autoresponder {$status} successfully!");
            
        } catch (Exception $e) {
            return back()->with('error', 'Failed to toggle autoresponder status');
        }
    }

    /**
     * Delete autoresponder
     */
    public function autorespondersDestroy($autoresponderId): RedirectResponse
    {
        try {
            $autoresponder = SmsAutoresponder::findOrFail($autoresponderId);
            $autoresponder->delete();
            
            return redirect()->route('sms.autoresponders.index')
                           ->with('success', 'Autoresponder deleted successfully!');
            
        } catch (Exception $e) {
            return back()->with('error', 'Failed to delete autoresponder');
        }
    }

    // ==================== SMS Analytics ====================

    /**
     * Analytics page
     */
    public function analytics(): View
    {
        try {
            // Get date range from request or default to last 30 days
            $startDate = request('start_date') ? Carbon::parse(request('start_date')) : now()->subDays(30);
            $endDate = request('end_date') ? Carbon::parse(request('end_date')) : now();
            
            // Overview statistics
            $overview = [
                'total_sent' => SmsLog::whereBetween('created_at', [$startDate, $endDate])->count(),
                'delivered' => SmsLog::whereBetween('created_at', [$startDate, $endDate])
                                    ->where('status', 'delivered')->count(),
                'failed' => SmsLog::whereBetween('created_at', [$startDate, $endDate])
                                 ->where('status', 'failed')->count(),
                'pending' => SmsLog::whereBetween('created_at', [$startDate, $endDate])
                                  ->where('status', 'pending')->count(),
            ];
            
            $overview['success_rate'] = $overview['total_sent'] > 0 
                ? round(($overview['delivered'] / $overview['total_sent']) * 100, 2) 
                : 0;
            
            // Campaign performance
            $campaignPerformance = SmsCampaign::with('template')
                                             ->whereBetween('created_at', [$startDate, $endDate])
                                             ->orderBy('sent_count', 'desc')
                                             ->limit(10)
                                             ->get();
            
            // Template usage
            $templateUsage = SmsTemplate::withCount(['logs' => function($query) use ($startDate, $endDate) {
                                        $query->whereBetween('created_at', [$startDate, $endDate]);
                                    }])
                                       ->orderBy('logs_count', 'desc')
                                       ->limit(10)
                                       ->get();
            
            return view('sms.analytics.index', compact(
                'overview',
                'campaignPerformance',
                'templateUsage',
                'startDate',
                'endDate'
            ));
            
        } catch (Exception $e) {
            Log::error('Analytics load failed', ['error' => $e->getMessage()]);
            
            return view('sms.analytics.index')->with('error', 'Failed to load analytics data');
        }
    }

    // ==================== SMS Logs ====================

    /**
     * SMS logs page
     */
    public function logs(): View
    {
        $logs = SmsLog::with(['template', 'campaign'])
                      ->orderBy('created_at', 'desc')
                      ->paginate(50);
        
        return view('sms.logs.index', compact('logs'));
    }

    // ==================== Settings ====================

    /**
     * Settings page
     */
    public function settings(): View
    {
        return view('sms.settings.index');
    }

    // ==================== Helper Methods ====================

    /**
     * Extract variables from content
     */
    private function extractVariables(string $content): array
    {
        preg_match_all('/{{\s*(\w+)\s*}}/', $content, $matches);
        return array_unique($matches[1] ?? []);
    }

    /**
     * Process recipients string/CSV into array
     */
    private function processRecipients(string $recipients): array
    {
        // Split by comma, newline, or semicolon
        $numbers = preg_split('/[,;\n\r]+/', $recipients);
        
        // Clean and validate each number
        $validNumbers = [];
        foreach ($numbers as $number) {
            $cleaned = trim($number);
            if (!empty($cleaned) && $this->isValidPhoneNumber($cleaned)) {
                $validNumbers[] = $this->formatPhoneNumber($cleaned);
            }
        }
        
        return array_unique($validNumbers);
    }

    /**
     * Validate phone number
     */
    private function isValidPhoneNumber(string $phone): bool
    {
        $cleaned = preg_replace('/[^+\d]/', '', $phone);
        return preg_match('/^\+?[1-9]\d{1,14}$/', $cleaned);
    }

    /**
     * Format phone number
     */
    private function formatPhoneNumber(string $phone): string
    {
        $cleaned = preg_replace('/[^+\d]/', '', $phone);
        
        if (!str_starts_with($cleaned, '+')) {
            $cleaned = '+' . $cleaned;
        }
        
        return $cleaned;
    }

    /**
     * Start campaign execution
     */
    private function startCampaign(SmsCampaign $campaign): void
    {
        try {
            $campaign->update(['status' => 'active', 'started_at' => now()]);
            
            // Get recipients
            $recipients = json_decode($campaign->recipients_data, true) ?? [];
            
            // Get message content
            $messageContent = $campaign->template_id 
                ? $campaign->template->content 
                : $campaign->message_content;
            
            // Send to each recipient (in real app, this should be queued)
            foreach ($recipients as $recipient) {
                try {
                    $result = Sms::to($recipient)
                                 ->message($messageContent)
                                 ->send();
                    
                    // Update campaign stats
                    $campaign->increment('sent_count');
                    
                    if ($result['status'] === 'delivered') {
                        $campaign->increment('delivered_count');
                    } else {
                        $campaign->increment('failed_count');
                    }
                    
                } catch (Exception $e) {
                    Log::error('Campaign SMS send failed', [
                        'campaign_id' => $campaign->id,
                        'recipient' => $recipient,
                        'error' => $e->getMessage()
                    ]);
                    
                    $campaign->increment('failed_count');
                }
            }
            
            // Mark campaign as completed if all sent
            if ($campaign->sent_count >= $campaign->total_recipients) {
                $campaign->update(['status' => 'completed', 'completed_at' => now()]);
            }
            
        } catch (Exception $e) {
            Log::error('Campaign execution failed', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage()
            ]);
            
            $campaign->update(['status' => 'failed']);
            throw $e;
        }
    }
}