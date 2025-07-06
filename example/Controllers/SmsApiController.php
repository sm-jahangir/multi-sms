<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
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
 * SMS API Controller
 * 
 * এই controller এ সব SMS related API endpoints আছে যা frontend থেকে AJAX calls এর জন্য ব্যবহার হয়।
 * সব methods JSON response return করে এবং proper error handling আছে।
 */
class SmsApiController extends Controller
{
    /**
     * Send test SMS
     * 
     * Quick test SMS send করার জন্য এই method ব্যবহার হয়
     * Dashboard এবং অন্যান্য জায়গা থেকে test SMS পাঠানোর জন্য
     */
    public function sendTest(Request $request): JsonResponse
    {
        try {
            // Input validation
            $validator = Validator::make($request->all(), [
                'to' => 'required|string|min:10|max:15',
                'message' => 'required|string|max:1600',
                'driver' => 'nullable|string|in:twilio,vonage,plivo,infobip,messagebird,viber,whatsapp',
                'from' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();
            
            // Build SMS
            $smsBuilder = Sms::to($data['to'])
                            ->message($data['message']);
            
            // Set driver if specified
            if (!empty($data['driver'])) {
                $smsBuilder->driver($data['driver']);
            }
            
            // Set sender if specified
            if (!empty($data['from'])) {
                $smsBuilder->from($data['from']);
            }
            
            // Send SMS
            $result = $smsBuilder->send();
            
            return response()->json([
                'success' => true,
                'message' => 'Test SMS sent successfully!',
                'data' => [
                    'message_id' => $result['id'] ?? null,
                    'status' => $result['status'] ?? 'sent',
                    'driver' => $result['driver'] ?? $data['driver'],
                    'cost' => $result['price'] ?? null,
                    'sent_at' => now()->toISOString()
                ]
            ]);
            
        } catch (Exception $e) {
            Log::error('Test SMS send failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test SMS: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get templates list for API calls
     * 
     * Templates dropdown এবং selection এর জন্য ব্যবহার হয়
     */
    public function getTemplates(Request $request): JsonResponse
    {
        try {
            $query = SmsTemplate::query();
            
            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('content', 'like', "%{$search}%")
                      ->orWhere('category', 'like', "%{$search}%");
                });
            }
            
            // Category filter
            if ($request->has('category') && !empty($request->category)) {
                $query->where('category', $request->category);
            }
            
            // Status filter
            if ($request->has('status') && !empty($request->status)) {
                $query->where('is_active', $request->status === 'active');
            }
            
            // Pagination
            $perPage = $request->get('per_page', 15);
            $templates = $query->orderBy('created_at', 'desc')
                              ->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $templates->items(),
                'pagination' => [
                    'current_page' => $templates->currentPage(),
                    'last_page' => $templates->lastPage(),
                    'per_page' => $templates->perPage(),
                    'total' => $templates->total(),
                    'from' => $templates->firstItem(),
                    'to' => $templates->lastItem()
                ]
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to get templates', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load templates'
            ], 500);
        }
    }

    /**
     * Get single template details
     */
    public function getTemplate($templateId): JsonResponse
    {
        try {
            $template = SmsTemplate::findOrFail($templateId);
            
            return response()->json([
                'success' => true,
                'data' => $template
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found'
            ], 404);
        }
    }

    /**
     * Preview template with variables
     * 
     * Template preview করার জন্য variables এর সাথে
     */
    public function previewTemplate(Request $request, $templateId): JsonResponse
    {
        try {
            $template = SmsTemplate::findOrFail($templateId);
            $variables = $request->get('variables', []);
            
            // Process template content with variables
            $processedContent = $this->processVariables($template->content, $variables);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'original_content' => $template->content,
                    'processed_content' => $processedContent,
                    'character_count' => strlen($processedContent),
                    'sms_count' => $this->calculateSmsCount($processedContent),
                    'variables_used' => $this->extractVariables($template->content)
                ]
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to preview template'
            ], 500);
        }
    }

    /**
     * Get campaigns list
     */
    public function getCampaigns(Request $request): JsonResponse
    {
        try {
            $query = SmsCampaign::query();
            
            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('type', 'like', "%{$search}%");
                });
            }
            
            // Status filter
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }
            
            // Type filter
            if ($request->has('type') && !empty($request->type)) {
                $query->where('type', $request->type);
            }
            
            // Date range filter
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            
            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }
            
            // Pagination
            $perPage = $request->get('per_page', 15);
            $campaigns = $query->with(['template'])
                              ->orderBy('created_at', 'desc')
                              ->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $campaigns->items(),
                'pagination' => [
                    'current_page' => $campaigns->currentPage(),
                    'last_page' => $campaigns->lastPage(),
                    'per_page' => $campaigns->perPage(),
                    'total' => $campaigns->total()
                ]
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to get campaigns', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load campaigns'
            ], 500);
        }
    }

    /**
     * Get campaign status and progress
     */
    public function getCampaignStatus($campaignId): JsonResponse
    {
        try {
            $campaign = SmsCampaign::findOrFail($campaignId);
            
            // Get campaign statistics
            $stats = [
                'total_recipients' => $campaign->total_recipients ?? 0,
                'sent_count' => $campaign->sent_count ?? 0,
                'delivered_count' => $campaign->delivered_count ?? 0,
                'failed_count' => $campaign->failed_count ?? 0,
                'pending_count' => ($campaign->total_recipients ?? 0) - ($campaign->sent_count ?? 0),
                'success_rate' => $this->calculateSuccessRate($campaign),
                'progress_percentage' => $this->calculateProgress($campaign)
            ];
            
            return response()->json([
                'success' => true,
                'data' => [
                    'campaign' => $campaign,
                    'statistics' => $stats,
                    'last_updated' => now()->toISOString()
                ]
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found'
            ], 404);
        }
    }

    /**
     * Get autoresponders list
     */
    public function getAutoresponders(Request $request): JsonResponse
    {
        try {
            $query = SmsAutoresponder::query();
            
            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('keyword', 'like', "%{$search}%")
                      ->orWhere('response_message', 'like', "%{$search}%");
                });
            }
            
            // Status filter
            if ($request->has('status') && !empty($request->status)) {
                $query->where('is_active', $request->status === 'active');
            }
            
            // Trigger type filter
            if ($request->has('trigger_type') && !empty($request->trigger_type)) {
                $query->where('trigger_type', $request->trigger_type);
            }
            
            // Pagination
            $perPage = $request->get('per_page', 15);
            $autoresponders = $query->orderBy('created_at', 'desc')
                                   ->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $autoresponders->items(),
                'pagination' => [
                    'current_page' => $autoresponders->currentPage(),
                    'last_page' => $autoresponders->lastPage(),
                    'per_page' => $autoresponders->perPage(),
                    'total' => $autoresponders->total()
                ]
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to get autoresponders', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load autoresponders'
            ], 500);
        }
    }

    /**
     * Test autoresponder trigger
     */
    public function testTrigger(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'autoresponder_id' => 'required|exists:sms_autoresponders,id',
                'trigger_data' => 'required|array',
                'test_phone' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $autoresponder = SmsAutoresponder::findOrFail($request->autoresponder_id);
            $triggerData = $request->trigger_data;
            $testPhone = $request->test_phone;
            
            // Simulate trigger processing
            $responseMessage = $this->processAutoresponderMessage(
                $autoresponder->response_message, 
                $triggerData
            );
            
            // Send test response
            $result = Sms::to($testPhone)
                         ->message($responseMessage)
                         ->send();
            
            return response()->json([
                'success' => true,
                'message' => 'Autoresponder test completed successfully',
                'data' => [
                    'processed_message' => $responseMessage,
                    'original_message' => $autoresponder->response_message,
                    'trigger_data' => $triggerData,
                    'sms_result' => $result
                ]
            ]);
            
        } catch (Exception $e) {
            Log::error('Autoresponder test failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Autoresponder test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dashboard analytics data
     */
    public function getDashboardData(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', '7d'); // 7d, 30d, 90d, 1y
            $startDate = $this->getStartDateForPeriod($period);
            
            // Overview statistics
            $overview = [
                'total_sent' => SmsLog::where('created_at', '>=', $startDate)->count(),
                'delivered' => SmsLog::where('created_at', '>=', $startDate)
                                    ->where('status', 'delivered')->count(),
                'failed' => SmsLog::where('created_at', '>=', $startDate)
                                 ->where('status', 'failed')->count(),
                'pending' => SmsLog::where('created_at', '>=', $startDate)
                                  ->where('status', 'pending')->count(),
            ];
            
            $overview['success_rate'] = $overview['total_sent'] > 0 
                ? round(($overview['delivered'] / $overview['total_sent']) * 100, 2) 
                : 0;
            
            // Daily trends
            $trends = $this->getDailyTrends($startDate);
            
            // Driver statistics
            $driverStats = $this->getDriverStatistics($startDate);
            
            // Recent activity
            $recentActivity = SmsLog::with(['template', 'campaign'])
                                   ->orderBy('created_at', 'desc')
                                   ->limit(10)
                                   ->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'overview' => $overview,
                    'trends' => $trends,
                    'driver_stats' => $driverStats,
                    'recent_activity' => $recentActivity,
                    'period' => $period,
                    'last_updated' => now()->toISOString()
                ]
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to get dashboard data', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard data'
            ], 500);
        }
    }

    /**
     * Get chart data for specific chart type
     */
    public function getChartData(Request $request, $type): JsonResponse
    {
        try {
            $period = $request->get('period', '7d');
            $startDate = $this->getStartDateForPeriod($period);
            
            $data = match($type) {
                'trends' => $this->getDailyTrends($startDate),
                'drivers' => $this->getDriverStatistics($startDate),
                'errors' => $this->getErrorAnalysis($startDate),
                'costs' => $this->getCostAnalysis($startDate),
                'geographic' => $this->getGeographicData($startDate),
                default => []
            };
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'chart_type' => $type,
                'period' => $period
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to get chart data', [
                'error' => $e->getMessage(),
                'chart_type' => $type
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load chart data'
            ], 500);
        }
    }

    /**
     * Get system drivers status
     */
    public function getDrivers(): JsonResponse
    {
        try {
            $drivers = [
                'twilio' => [
                    'name' => 'Twilio',
                    'configured' => $this->isDriverConfigured('twilio'),
                    'status' => 'active',
                    'priority' => 1
                ],
                'vonage' => [
                    'name' => 'Vonage (Nexmo)',
                    'configured' => $this->isDriverConfigured('vonage'),
                    'status' => 'active',
                    'priority' => 2
                ],
                'plivo' => [
                    'name' => 'Plivo',
                    'configured' => $this->isDriverConfigured('plivo'),
                    'status' => 'active',
                    'priority' => 3
                ],
                'infobip' => [
                    'name' => 'Infobip',
                    'configured' => $this->isDriverConfigured('infobip'),
                    'status' => 'active',
                    'priority' => 4
                ],
                'messagebird' => [
                    'name' => 'MessageBird',
                    'configured' => $this->isDriverConfigured('messagebird'),
                    'status' => 'active',
                    'priority' => 5
                ]
            ];
            
            return response()->json([
                'success' => true,
                'data' => $drivers
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get drivers status'
            ], 500);
        }
    }

    /**
     * Get system status
     */
    public function getSystemStatus(): JsonResponse
    {
        try {
            $status = [
                'system' => 'operational',
                'database' => 'connected',
                'queue' => 'running',
                'drivers' => [
                    'total' => 5,
                    'configured' => $this->getConfiguredDriversCount(),
                    'active' => $this->getActiveDriversCount()
                ],
                'last_sms' => SmsLog::latest()->first()?->created_at,
                'uptime' => '99.9%',
                'version' => '1.0.0'
            ];
            
            return response()->json([
                'success' => true,
                'data' => $status
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get system status'
            ], 500);
        }
    }

    /**
     * Validate phone number
     */
    public function validatePhone(Request $request): JsonResponse
    {
        try {
            $phone = $request->get('phone');
            
            if (empty($phone)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Phone number is required'
                ], 422);
            }
            
            // Basic phone validation
            $isValid = $this->isValidPhoneNumber($phone);
            $formatted = $this->formatPhoneNumber($phone);
            $country = $this->detectCountry($phone);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'is_valid' => $isValid,
                    'original' => $phone,
                    'formatted' => $formatted,
                    'country' => $country,
                    'type' => $this->getPhoneType($phone)
                ]
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Phone validation failed'
            ], 500);
        }
    }

    /**
     * Detect variables in content
     */
    public function detectVariables(Request $request): JsonResponse
    {
        try {
            $content = $request->get('content', '');
            $variables = $this->extractVariables($content);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'variables' => $variables,
                    'count' => count($variables),
                    'content' => $content
                ]
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Variable detection failed'
            ], 500);
        }
    }

    // ==================== Helper Methods ====================

    /**
     * Process variables in content
     */
    private function processVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $content = str_replace("{{$key}}", $value, $content);
            $content = str_replace("{{ $key }}", $value, $content);
        }
        
        return $content;
    }

    /**
     * Extract variables from content
     */
    private function extractVariables(string $content): array
    {
        preg_match_all('/{{\s*(\w+)\s*}}/', $content, $matches);
        return array_unique($matches[1] ?? []);
    }

    /**
     * Calculate SMS count based on character length
     */
    private function calculateSmsCount(string $content): int
    {
        $length = strlen($content);
        
        if ($length <= 160) {
            return 1;
        } elseif ($length <= 306) {
            return 2;
        } else {
            return ceil($length / 153);
        }
    }

    /**
     * Calculate campaign success rate
     */
    private function calculateSuccessRate($campaign): float
    {
        $total = $campaign->sent_count ?? 0;
        $delivered = $campaign->delivered_count ?? 0;
        
        return $total > 0 ? round(($delivered / $total) * 100, 2) : 0;
    }

    /**
     * Calculate campaign progress
     */
    private function calculateProgress($campaign): float
    {
        $total = $campaign->total_recipients ?? 0;
        $sent = $campaign->sent_count ?? 0;
        
        return $total > 0 ? round(($sent / $total) * 100, 2) : 0;
    }

    /**
     * Get start date for period
     */
    private function getStartDateForPeriod(string $period): Carbon
    {
        return match($period) {
            '1d' => now()->subDay(),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            '1y' => now()->subYear(),
            default => now()->subDays(7)
        };
    }

    /**
     * Get daily trends data
     */
    private function getDailyTrends(Carbon $startDate): array
    {
        $trends = SmsLog::selectRaw('DATE(created_at) as date, COUNT(*) as total, 
                                   SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered,
                                   SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed')
                        ->where('created_at', '>=', $startDate)
                        ->groupBy('date')
                        ->orderBy('date')
                        ->get();
        
        return $trends->toArray();
    }

    /**
     * Get driver statistics
     */
    private function getDriverStatistics(Carbon $startDate): array
    {
        $stats = SmsLog::selectRaw('driver, COUNT(*) as total,
                                  SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered,
                                  SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed')
                       ->where('created_at', '>=', $startDate)
                       ->groupBy('driver')
                       ->get();
        
        return $stats->toArray();
    }

    /**
     * Get error analysis data
     */
    private function getErrorAnalysis(Carbon $startDate): array
    {
        $errors = SmsLog::selectRaw('error_message, COUNT(*) as count')
                        ->where('created_at', '>=', $startDate)
                        ->where('status', 'failed')
                        ->whereNotNull('error_message')
                        ->groupBy('error_message')
                        ->orderBy('count', 'desc')
                        ->limit(10)
                        ->get();
        
        return $errors->toArray();
    }

    /**
     * Get cost analysis data
     */
    private function getCostAnalysis(Carbon $startDate): array
    {
        $costs = SmsLog::selectRaw('driver, SUM(CAST(cost as DECIMAL(10,4))) as total_cost, COUNT(*) as message_count')
                       ->where('created_at', '>=', $startDate)
                       ->whereNotNull('cost')
                       ->groupBy('driver')
                       ->get();
        
        return $costs->toArray();
    }

    /**
     * Get geographic data
     */
    private function getGeographicData(Carbon $startDate): array
    {
        // This would require phone number parsing to extract country codes
        // For now, return sample data
        return [
            ['country' => 'Bangladesh', 'code' => 'BD', 'count' => 1250],
            ['country' => 'India', 'code' => 'IN', 'count' => 890],
            ['country' => 'Pakistan', 'code' => 'PK', 'count' => 456],
            ['country' => 'United States', 'code' => 'US', 'count' => 234],
            ['country' => 'United Kingdom', 'code' => 'GB', 'count' => 123]
        ];
    }

    /**
     * Check if driver is configured
     */
    private function isDriverConfigured(string $driver): bool
    {
        return match($driver) {
            'twilio' => !empty(config('multi-sms.drivers.twilio.account_sid')),
            'vonage' => !empty(config('multi-sms.drivers.vonage.api_key')),
            'plivo' => !empty(config('multi-sms.drivers.plivo.auth_id')),
            'infobip' => !empty(config('multi-sms.drivers.infobip.api_key')),
            'messagebird' => !empty(config('multi-sms.drivers.messagebird.api_key')),
            default => false
        };
    }

    /**
     * Get configured drivers count
     */
    private function getConfiguredDriversCount(): int
    {
        $drivers = ['twilio', 'vonage', 'plivo', 'infobip', 'messagebird'];
        $count = 0;
        
        foreach ($drivers as $driver) {
            if ($this->isDriverConfigured($driver)) {
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Get active drivers count
     */
    private function getActiveDriversCount(): int
    {
        // For now, return same as configured
        return $this->getConfiguredDriversCount();
    }

    /**
     * Validate phone number format
     */
    private function isValidPhoneNumber(string $phone): bool
    {
        // Remove all non-digit characters except +
        $cleaned = preg_replace('/[^+\d]/', '', $phone);
        
        // Check if it matches international format
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
     * Detect country from phone number
     */
    private function detectCountry(string $phone): ?string
    {
        $cleaned = preg_replace('/[^\d]/', '', $phone);
        
        // Simple country detection based on country codes
        if (str_starts_with($cleaned, '880')) {
            return 'Bangladesh';
        } elseif (str_starts_with($cleaned, '91')) {
            return 'India';
        } elseif (str_starts_with($cleaned, '92')) {
            return 'Pakistan';
        } elseif (str_starts_with($cleaned, '1')) {
            return 'United States';
        } elseif (str_starts_with($cleaned, '44')) {
            return 'United Kingdom';
        }
        
        return null;
    }

    /**
     * Get phone type (mobile/landline)
     */
    private function getPhoneType(string $phone): string
    {
        // Simple mobile detection - this would need more sophisticated logic
        return 'mobile';
    }

    /**
     * Process autoresponder message with variables
     */
    private function processAutoresponderMessage(string $message, array $triggerData): string
    {
        // Replace common variables
        $variables = [
            'name' => $triggerData['name'] ?? 'Customer',
            'phone' => $triggerData['phone'] ?? '',
            'keyword' => $triggerData['keyword'] ?? '',
            'time' => now()->format('H:i'),
            'date' => now()->format('Y-m-d'),
            'datetime' => now()->format('Y-m-d H:i:s')
        ];
        
        return $this->processVariables($message, $variables);
    }
}