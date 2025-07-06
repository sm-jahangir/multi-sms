<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use MultiSms\Facades\Sms;
use MultiSms\Models\SmsTemplate;
use MultiSms\Models\SmsCampaign;
use MultiSms\Models\SmsLog;
use MultiSms\Models\SmsAutoresponder;
use MultiSms\Models\SmsTrigger;
use MultiSms\Models\SmsAutomationLog;
use Exception;

/**
 * SMS Test Controller
 *
 * This controller provides comprehensive testing functionality for the Multi-SMS package.
 * It includes methods to test all major features including templates, campaigns,
 * autoresponders, analytics, and service methods.
 *
 * All methods return JSON responses for easy API testing.
 */
class SmsTestController extends Controller
{
    /**
     * Get all SMS templates with their details
     *
     * This method retrieves all SMS templates from the database and returns
     * their complete information including variables and status.
     *
     * @return JsonResponse
     */
    public function getTemplates(): JsonResponse
    {
        try {
            $templates = SmsTemplate::all();

            return response()->json([
                'status' => 'success',
                'message' => 'SMS Templates retrieved successfully',
                'count' => $templates->count(),
                'templates' => $templates->map(function ($template) {
                    return [
                        'id' => $template->id,
                        'key' => $template->key,
                        'name' => $template->name,
                        'body' => $template->body,
                        'tags' => $template->tags,
                        'variables' => $template->variables,
                        'is_active' => $template->is_active,
                        'created_at' => $template->created_at,
                        'updated_at' => $template->updated_at
                    ];
                })
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve templates',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send SMS using a specific template
     *
     * This method finds a template by ID, processes its variables with sample data,
     * and sends an SMS using the processed template content.
     *
     * @param int $templateId The ID of the template to use
     * @return JsonResponse
     */
    public function sendWithTemplate(int $templateId): JsonResponse
    {
        $template = SmsTemplate::findOrFail($templateId);
        try {
            $template = SmsTemplate::findOrFail($templateId);

            // Process template variables with sample data
            $message = $this->processTemplateVariables($template);

            // Send SMS using the processed template
            $result = Sms::to('+8801767275819')
                ->message($message)
                ->send();

            return response()->json([
                'status' => 'success',
                'message' => 'SMS sent using template: ' . $template->name,
                'template' => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'original_body' => $template->body,
                    'processed_message' => $message
                ],
                'sms_result' => $result
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send SMS using template',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all SMS campaigns with their statistics
     *
     * This method retrieves all campaigns along with their related logs
     * and provides comprehensive campaign statistics.
     *
     * @return JsonResponse
     */
    public function getCampaigns(): JsonResponse
    {
        try {
            $campaigns = SmsCampaign::with('smsLogs')->get();

            return response()->json([
                'status' => 'success',
                'message' => 'SMS Campaigns retrieved successfully',
                'count' => $campaigns->count(),
                'campaigns' => $campaigns->map(function ($campaign) {
                    return [
                        'id' => $campaign->id,
                        'name' => $campaign->name,
                        'message' => $campaign->message,
                        'status' => $campaign->status,
                        'total_recipients' => $campaign->total_recipients,
                        'sent_count' => $campaign->sent_count,
                        'failed_count' => $campaign->failed_count,
                        'success_rate' => $campaign->total_recipients > 0
                            ? round(($campaign->sent_count / $campaign->total_recipients) * 100, 2)
                            : 0,
                        'scheduled_at' => $campaign->scheduled_at,
                        'started_at' => $campaign->started_at,
                        'completed_at' => $campaign->completed_at,
                        'driver' => $campaign->driver,
                        'from_number' => $campaign->from_number,
                        'logs_count' => $campaign->smsLogs->count(),
                        'created_at' => $campaign->created_at
                    ];
                })
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve campaigns',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get comprehensive SMS analytics
     *
     * This method provides detailed analytics including message counts,
     * success rates, driver statistics, and recent activity.
     *
     * @return JsonResponse
     */
    public function getAnalytics(): JsonResponse
    {
        try {
            // Basic message statistics
            $totalLogs = SmsLog::count();
            $sentCount = SmsLog::where('status', 'sent')->count();
            $failedCount = SmsLog::where('status', 'failed')->count();
            $pendingCount = SmsLog::where('status', 'pending')->count();

            // Driver performance statistics
            $driverStats = SmsLog::select('driver')
                ->selectRaw('COUNT(*) as total')
                ->selectRaw('SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent')
                ->selectRaw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed')
                ->selectRaw('SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending')
                ->selectRaw('SUM(cost) as total_cost')
                ->selectRaw('AVG(cost) as avg_cost')
                ->groupBy('driver')
                ->get()
                ->map(function ($stat) {
                    $stat->success_rate = $stat->total > 0
                        ? round(($stat->sent / $stat->total) * 100, 2)
                        : 0;
                    return $stat;
                });

            // Recent activity
            $recentLogs = SmsLog::orderBy('created_at', 'desc')
                ->limit(10)
                ->get(['id', 'to', 'driver', 'status', 'cost', 'sent_at', 'created_at', 'error_message']);

            // Daily statistics for the last 7 days
            $dailyStats = SmsLog::selectRaw('DATE(created_at) as date')
                ->selectRaw('COUNT(*) as total')
                ->selectRaw('SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent')
                ->selectRaw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed')
                ->where('created_at', '>=', now()->subDays(7))
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'SMS Analytics retrieved successfully',
                'analytics' => [
                    'overview' => [
                        'total_messages' => $totalLogs,
                        'sent_messages' => $sentCount,
                        'failed_messages' => $failedCount,
                        'pending_messages' => $pendingCount,
                        'overall_success_rate' => $totalLogs > 0 ? round(($sentCount / $totalLogs) * 100, 2) : 0,
                        'total_cost' => SmsLog::sum('cost') ?? 0
                    ],
                    'driver_statistics' => $driverStats,
                    'recent_activity' => $recentLogs,
                    'daily_statistics' => $dailyStats
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all autoresponders with their configuration
     *
     * This method retrieves all autoresponders along with their triggers
     * and automation logs for comprehensive overview.
     *
     * @return JsonResponse
     */
    public function getAutoresponders(): JsonResponse
    {
        try {
            $autoresponders = SmsAutoresponder::with(['triggers', 'automationLogs'])->get();

            return response()->json([
                'status' => 'success',
                'message' => 'SMS Autoresponders retrieved successfully',
                'count' => $autoresponders->count(),
                'autoresponders' => $autoresponders->map(function ($autoresponder) {
                    return [
                        'id' => $autoresponder->id,
                        'name' => $autoresponder->name,
                        'trigger_type' => $autoresponder->trigger_type,
                        'trigger_value' => $autoresponder->trigger_value,
                        'response_message' => $autoresponder->response_message,
                        'template_id' => $autoresponder->template_id,
                        'is_active' => $autoresponder->is_active,
                        'delay_minutes' => $autoresponder->delay_minutes,
                        'max_triggers_per_number' => $autoresponder->max_triggers_per_number,
                        'triggers_count' => $autoresponder->triggers->count(),
                        'automation_logs_count' => $autoresponder->automationLogs->count(),
                        'success_rate' => $autoresponder->automationLogs->count() > 0
                            ? round(($autoresponder->automationLogs->where('status', 'success')->count() / $autoresponder->automationLogs->count()) * 100, 2)
                            : 0,
                        'created_at' => $autoresponder->created_at
                    ];
                })
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve autoresponders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Trigger an autoresponder by keyword
     *
     * This method simulates triggering an autoresponder by finding one
     * that matches the given keyword and executing its response.
     *
     * @param string $keyword The keyword to trigger
     * @return JsonResponse
     */
    public function triggerAutoresponder(string $keyword): JsonResponse
    {
        try {
            // Find active autoresponder for the keyword
            $autoresponder = SmsAutoresponder::where('trigger_type', 'keyword')
                ->where('is_active', true)
                ->get()
                ->filter(function ($ar) use ($keyword) {
                    $triggerValue = json_decode($ar->trigger_value, true);
                    $keywords = $triggerValue['keywords'] ?? [];
                    return in_array(strtoupper($keyword), array_map('strtoupper', $keywords));
                })
                ->first();

            if (!$autoresponder) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No active autoresponder found for keyword: ' . $keyword,
                    'available_keywords' => $this->getAvailableKeywords()
                ], 404);
            }

            // Create trigger record
            $trigger = SmsTrigger::create([
                'autoresponder_id' => $autoresponder->id,
                'phone_number' => '+8801767275819',
                'trigger_type' => 'keyword',
                'trigger_data' => json_encode([
                    'keyword' => strtoupper($keyword),
                    'original_message' => $keyword,
                    'test_trigger' => true
                ]),
                'response_sent' => false
            ]);

            // Prepare response message
            $message = $autoresponder->response_message;
            if ($autoresponder->template_id) {
                $template = SmsTemplate::find($autoresponder->template_id);
                if ($template) {
                    $message = $this->processTemplateVariables($template);
                }
            }

            // Send autoresponse
            $result = Sms::to('+8801767275819')
                ->message($message)
                ->send();

            // Update trigger record
            $trigger->update([
                'response_sent' => $result['success'] ?? false,
                'response_message_id' => $result['message_id'] ?? null,
                'processed_at' => now(),
                'error_message' => $result['success'] ? null : ($result['error'] ?? 'Unknown error')
            ]);

            // Create automation log
            SmsAutomationLog::create([
                'autoresponder_id' => $autoresponder->id,
                'trigger_id' => $trigger->id,
                'phone_number' => '+8801767275819',
                'trigger_type' => 'keyword',
                'trigger_value' => strtoupper($keyword),
                'response_message' => $message,
                'status' => $result['success'] ? 'success' : 'failed',
                'message_id' => $result['message_id'] ?? null,
                'driver_used' => $result['driver'] ?? null,
                'execution_time_ms' => rand(200, 800),
                'error_message' => $result['success'] ? null : ($result['error'] ?? 'Unknown error'),
                'context_data' => json_encode([
                    'keyword' => strtoupper($keyword),
                    'autoresponder_name' => $autoresponder->name,
                    'test_trigger' => true,
                    'triggered_at' => now()->toISOString()
                ])
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Autoresponder triggered successfully for keyword: ' . $keyword,
                'autoresponder' => [
                    'id' => $autoresponder->id,
                    'name' => $autoresponder->name,
                    'trigger_type' => $autoresponder->trigger_type
                ],
                'trigger_id' => $trigger->id,
                'response_message' => $message,
                'sms_result' => $result
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to trigger autoresponder',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create and execute a test campaign
     *
     * This method creates a new SMS campaign with test data and immediately
     * executes it, sending messages to predefined test recipients.
     *
     * @return JsonResponse
     */
    public function createTestCampaign(): JsonResponse
    {
        try {
            // Create test campaign
            $campaign = SmsCampaign::create([
                'name' => 'Test Campaign - ' . now()->format('Y-m-d H:i:s'),
                'message' => 'This is a test campaign message created via API. Thank you for testing our SMS service! 🚀',
                'recipients' => json_encode(['+8801767275819', '+1234567891', '+1234567892']),
                'status' => 'running',
                'scheduled_at' => now(),
                'started_at' => now(),
                'driver' => 'twilio',
                'from_number' => env('TWILIO_FROM'),
                'total_recipients' => 3,
                'sent_count' => 0,
                'failed_count' => 0,
                'settings' => json_encode([
                    'send_rate' => 5,
                    'retry_failed' => true,
                    'track_clicks' => false,
                    'test_campaign' => true
                ]),
                'created_by' => 1
            ]);

            // Execute campaign
            $executionResult = $this->executeCampaign($campaign);

            return response()->json([
                'status' => 'success',
                'message' => 'Test campaign created and executed successfully',
                'campaign' => [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'total_recipients' => $campaign->total_recipients,
                    'sent_count' => $executionResult['sent_count'],
                    'failed_count' => $executionResult['failed_count'],
                    'success_rate' => $campaign->total_recipients > 0
                        ? round(($executionResult['sent_count'] / $campaign->total_recipients) * 100, 2)
                        : 0,
                    'status' => $campaign->fresh()->status,
                    'created_at' => $campaign->created_at
                ],
                'execution_details' => $executionResult['results']
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create and execute campaign',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test SMS service methods
     *
     * This method tests various SMS service methods including driver
     * availability and configuration status.
     *
     * @return JsonResponse
     */
    public function testServiceMethods(): JsonResponse
    {
        try {
            $smsService = app('sms');

            return response()->json([
                'status' => 'success',
                'message' => 'SMS Service methods tested successfully',
                'service_info' => [
                    'available_drivers' => $smsService->getAvailableDrivers(),
                    'driver_configurations' => [
                        'twilio_configured' => $smsService->isDriverConfigured('twilio'),
                        'vonage_configured' => $smsService->isDriverConfigured('vonage'),
                        'plivo_configured' => $smsService->isDriverConfigured('plivo'),
                        'infobip_configured' => $smsService->isDriverConfigured('infobip'),
                        'messagebird_configured' => $smsService->isDriverConfigured('messagebird')
                    ],
                    'default_driver' => config('sms.default'),
                    'fallback_enabled' => config('sms.fallback.enabled', false)
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to test service methods',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Run the SMS Marketing Seeder
     *
     * This method executes the SMS Marketing Seeder to populate
     * the database with test data.
     *
     * @return JsonResponse
     */
    public function runSeeder(): JsonResponse
    {
        try {
            Artisan::call('db:seed', [
                '--class' => '\\MultiSms\\Database\\Seeders\\SmsMarketingSeeder'
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'SMS Marketing Seeder executed successfully',
                'output' => Artisan::output(),
                'next_steps' => [
                    'Visit /sms-test/templates to view seeded templates',
                    'Visit /sms-test/campaigns to view seeded campaigns',
                    'Visit /sms-test/autoresponders to view seeded autoresponders',
                    'Visit /sms-test/logs/analytics to view analytics'
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to run seeder',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available test routes
     *
     * This method returns a list of all available test routes
     * for easy navigation and testing.
     *
     * @return JsonResponse
     */
    public function getTestRoutes(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Available SMS test routes',
            'routes' => [
                'seeder' => [
                    'GET /sms-test/run-seeder' => 'Run the SMS Marketing Seeder'
                ],
                'templates' => [
                    'GET /sms-test/templates' => 'Get all SMS templates',
                    'GET /sms-test/send-template/{id}' => 'Send SMS using template'
                ],
                'campaigns' => [
                    'GET /sms-test/campaigns' => 'Get all SMS campaigns',
                    'GET /sms-test/create-campaign' => 'Create and execute test campaign'
                ],
                'analytics' => [
                    'GET /sms-test/logs/analytics' => 'Get comprehensive SMS analytics'
                ],
                'autoresponders' => [
                    'GET /sms-test/autoresponders' => 'Get all autoresponders',
                    'GET /sms-test/trigger-autoresponder/{keyword}' => 'Trigger autoresponder by keyword'
                ],
                'service' => [
                    'GET /sms-test/service-methods' => 'Test SMS service methods',
                    'GET /sms-test/routes' => 'Get this list of available routes'
                ],
                'basic_sms' => [
                    'GET /test-sms' => 'Test fluent interface SMS sending',
                    'GET /test-sms-simple' => 'Test simple API SMS sending',
                    'GET /test-sms-bulk' => 'Test bulk SMS sending'
                ]
            ]
        ]);
    }

    /**
     * Process template variables with sample data
     *
     * This helper method replaces template variables with realistic sample data.
     *
     * @param SmsTemplate $template
     * @return string
     */
    private function processTemplateVariables(SmsTemplate $template): string
    {
        $message = $template->body;
        $variables = json_decode($template->variables, true) ?? [];

        foreach ($variables as $variable) {
            $sampleValue = match($variable) {
                'company_name' => 'Example Company',
                'customer_name' => 'John Doe',
                'order_id' => '12345',
                'amount' => '99.99',
                'delivery_date' => '2024-01-20',
                'shop_url' => 'https://shop.example.com',
                'provider_name' => 'Dr. Smith',
                'date' => '2024-01-15',
                'time' => '2:00 PM',
                'address' => '123 Main St, City',
                'item_count' => '3',
                'cart_url' => 'https://shop.example.com/cart',
                'expiry_date' => '2024-01-31',
                'tracking_url' => 'https://track.example.com/ABC123',
                'tracking_number' => 'ABC123',
                'review_url' => 'https://review.example.com/12345',
                default => 'Sample Value'
            };
            $message = str_replace('{{' . $variable . '}}', $sampleValue, $message);
        }

        return $message;
    }

    /**
     * Execute a campaign by sending messages to all recipients
     *
     * This helper method handles the actual execution of a campaign.
     *
     * @param SmsCampaign $campaign
     * @return array
     */
    private function executeCampaign(SmsCampaign $campaign): array
    {
        $recipients = json_decode($campaign->recipients, true);
        $results = [];
        $sentCount = 0;
        $failedCount = 0;

        foreach ($recipients as $recipient) {
            try {
                $result = Sms::to($recipient)
                    ->message($campaign->message)
                    ->driver($campaign->driver)
                    ->from($campaign->from_number)
                    ->send();

                $results[] = [
                    'recipient' => $recipient,
                    'status' => $result['success'] ? 'sent' : 'failed',
                    'message_id' => $result['message_id'] ?? null,
                    'error' => $result['success'] ? null : ($result['error'] ?? 'Unknown error')
                ];

                if ($result['success']) {
                    $sentCount++;
                } else {
                    $failedCount++;
                }

                // Create log entry
                SmsLog::create([
                    'to' => $recipient,
                    'from' => $campaign->from_number,
                    'body' => $campaign->message,
                    'driver' => $campaign->driver,
                    'status' => $result['success'] ? 'sent' : 'failed',
                    'response' => json_encode($result['response'] ?? []),
                    'sent_at' => $result['success'] ? now() : null,
                    'campaign_id' => $campaign->id,
                    'template_id' => null,
                    'cost' => $result['success'] ? 0.0075 : null,
                    'message_id' => $result['message_id'] ?? null,
                    'error_message' => $result['success'] ? null : ($result['error'] ?? 'Unknown error')
                ]);

            } catch (Exception $e) {
                $results[] = [
                    'recipient' => $recipient,
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
                $failedCount++;
            }
        }

        // Update campaign status
        $campaign->update([
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'status' => 'completed',
            'completed_at' => now()
        ]);

        return [
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'results' => $results
        ];
    }

    /**
     * Get available keywords from active autoresponders
     *
     * This helper method returns a list of available keywords for testing.
     *
     * @return array
     */
    private function getAvailableKeywords(): array
    {
        $keywords = [];

        $autoresponders = SmsAutoresponder::where('trigger_type', 'keyword')
            ->where('is_active', true)
            ->get();

        foreach ($autoresponders as $autoresponder) {
            $triggerValue = json_decode($autoresponder->trigger_value, true);
            if (isset($triggerValue['keywords'])) {
                $keywords = array_merge($keywords, $triggerValue['keywords']);
            }
        }

        return array_unique($keywords);
    }
}
