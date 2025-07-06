<?php

namespace MultiSms\Database\Seeders;

use Illuminate\Database\Seeder;
use MultiSms\Models\SmsTemplate;
use MultiSms\Models\SmsCampaign;
use MultiSms\Models\SmsLog;
use MultiSms\Models\SmsAutoresponder;
use MultiSms\Models\SmsTrigger;
use MultiSms\Models\SmsAutomationLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * SMS Marketing Seeder
 * 
 * This seeder creates realistic test data for SMS marketing functionality.
 * It populates all SMS-related tables with sample data to test the complete
 * SMS marketing workflow including templates, campaigns, logs, autoresponders,
 * triggers, and automation logs.
 */
class SmsMarketingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * This method orchestrates the seeding process by calling individual
     * seeding methods in the correct order to maintain referential integrity.
     */
    public function run(): void
    {
        // Disable foreign key checks to avoid constraint issues during seeding
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Clear existing data to ensure clean seeding
        $this->clearExistingData();
        
        // Seed data in order of dependencies
        $this->seedSmsTemplates();
        $this->seedSmsCampaigns();
        $this->seedSmsLogs();
        $this->seedSmsAutoresponders();
        $this->seedSmsTriggers();
        $this->seedSmsAutomationLogs();
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $this->command->info('SMS Marketing seeder completed successfully!');
    }
    
    /**
     * Clear existing data from all SMS tables
     * 
     * This ensures we start with a clean slate for testing.
     */
    private function clearExistingData(): void
    {
        SmsAutomationLog::truncate();
        SmsTrigger::truncate();
        SmsAutoresponder::truncate();
        SmsLog::truncate();
        SmsCampaign::truncate();
        SmsTemplate::truncate();
        
        $this->command->info('Cleared existing SMS data.');
    }
    
    /**
     * Seed SMS templates
     * 
     * Creates various SMS templates for different marketing purposes
     * including promotional, transactional, and automated messages.
     */
    private function seedSmsTemplates(): void
    {
        $templates = [
            [
                'key' => 'welcome_message',
                'name' => 'Welcome Message',
                'body' => 'Welcome to {{company_name}}! Thanks for joining us. Use code WELCOME10 for 10% off your first order. Reply STOP to opt out.',
                'tags' => json_encode(['welcome', 'promotional', 'discount']),
                'variables' => json_encode(['company_name']),
                'is_active' => true,
                'description' => 'Welcome message sent to new subscribers with discount code',
            ],
            [
                'key' => 'order_confirmation',
                'name' => 'Order Confirmation',
                'body' => 'Hi {{customer_name}}, your order #{{order_id}} has been confirmed. Total: ${{amount}}. Expected delivery: {{delivery_date}}.',
                'tags' => json_encode(['transactional', 'order', 'confirmation']),
                'variables' => json_encode(['customer_name', 'order_id', 'amount', 'delivery_date']),
                'is_active' => true,
                'description' => 'Transactional message for order confirmations',
            ],
            [
                'key' => 'flash_sale',
                'name' => 'Flash Sale Alert',
                'body' => '🔥 FLASH SALE! 50% OFF everything for the next 2 hours only! Shop now: {{shop_url}} Use code: FLASH50',
                'tags' => json_encode(['promotional', 'urgent', 'sale']),
                'variables' => json_encode(['shop_url']),
                'is_active' => true,
                'description' => 'Urgent promotional message for flash sales',
            ],
            [
                'key' => 'appointment_reminder',
                'name' => 'Appointment Reminder',
                'body' => 'Reminder: You have an appointment with {{provider_name}} on {{date}} at {{time}}. Location: {{address}}. Reply C to confirm.',
                'tags' => json_encode(['reminder', 'appointment', 'healthcare']),
                'variables' => json_encode(['provider_name', 'date', 'time', 'address']),
                'is_active' => true,
                'description' => 'Appointment reminder for healthcare providers',
            ],
            [
                'key' => 'abandoned_cart',
                'name' => 'Abandoned Cart Recovery',
                'body' => 'You left {{item_count}} items in your cart! Complete your purchase now and get FREE shipping: {{cart_url}}',
                'tags' => json_encode(['recovery', 'cart', 'ecommerce']),
                'variables' => json_encode(['item_count', 'cart_url']),
                'is_active' => true,
                'description' => 'Cart abandonment recovery message',
            ],
            [
                'key' => 'birthday_offer',
                'name' => 'Birthday Special Offer',
                'body' => 'Happy Birthday {{customer_name}}! 🎉 Enjoy 25% off as our gift to you. Use code: BIRTHDAY25. Valid until {{expiry_date}}.',
                'tags' => json_encode(['birthday', 'personal', 'discount']),
                'variables' => json_encode(['customer_name', 'expiry_date']),
                'is_active' => true,
                'description' => 'Personalized birthday offer message',
            ],
            [
                'key' => 'shipping_notification',
                'name' => 'Shipping Notification',
                'body' => 'Great news! Your order #{{order_id}} has shipped. Track your package: {{tracking_url}} Tracking #: {{tracking_number}}',
                'tags' => json_encode(['shipping', 'transactional', 'tracking']),
                'variables' => json_encode(['order_id', 'tracking_url', 'tracking_number']),
                'is_active' => true,
                'description' => 'Shipping notification with tracking information',
            ],
            [
                'key' => 'feedback_request',
                'name' => 'Feedback Request',
                'body' => 'How was your recent experience with us? Please rate us: {{review_url}} Your feedback helps us improve!',
                'tags' => json_encode(['feedback', 'review', 'customer_service']),
                'variables' => json_encode(['review_url']),
                'is_active' => true,
                'description' => 'Post-purchase feedback request',
            ],
        ];
        
        foreach ($templates as $template) {
            SmsTemplate::create($template);
        }
        
        $this->command->info('Created ' . count($templates) . ' SMS templates.');
    }
    
    /**
     * Seed SMS campaigns
     * 
     * Creates various marketing campaigns with different statuses and configurations
     * to test campaign management functionality.
     */
    private function seedSmsCampaigns(): void
    {
        $campaigns = [
            [
                'name' => 'Black Friday 2024 Campaign',
                'message' => '🛍️ BLACK FRIDAY MEGA SALE! Up to 70% OFF everything! Shop now before items sell out: https://shop.example.com/blackfriday',
                'recipients' => json_encode([
                    '+1234567890', '+1234567891', '+1234567892', '+1234567893', '+1234567894',
                    '+1234567895', '+1234567896', '+1234567897', '+1234567898', '+1234567899'
                ]),
                'status' => 'completed',
                'scheduled_at' => Carbon::now()->subDays(2),
                'started_at' => Carbon::now()->subDays(2)->addMinutes(5),
                'completed_at' => Carbon::now()->subDays(2)->addHours(1),
                'template_id' => 3, // Flash sale template
                'driver' => 'twilio',
                'from_number' => '+1987654321',
                'total_recipients' => 10,
                'sent_count' => 9,
                'failed_count' => 1,
                'settings' => json_encode([
                    'send_rate' => 10, // messages per minute
                    'retry_failed' => true,
                    'track_clicks' => true
                ]),
                'created_by' => 1,
            ],
            [
                'name' => 'New Product Launch - Summer Collection',
                'message' => '☀️ NEW ARRIVALS! Check out our stunning Summer Collection. Be the first to shop: https://shop.example.com/summer',
                'recipients' => json_encode([
                    '+1234567800', '+1234567801', '+1234567802', '+1234567803', '+1234567804'
                ]),
                'status' => 'running',
                'scheduled_at' => Carbon::now()->subHours(2),
                'started_at' => Carbon::now()->subHours(2)->addMinutes(10),
                'completed_at' => null,
                'template_id' => null,
                'driver' => 'vonage',
                'from_number' => '+1987654322',
                'total_recipients' => 5,
                'sent_count' => 3,
                'failed_count' => 0,
                'settings' => json_encode([
                    'send_rate' => 5,
                    'retry_failed' => true,
                    'track_clicks' => false
                ]),
                'created_by' => 1,
            ],
            [
                'name' => 'Customer Appreciation Week',
                'message' => 'Thank you for being a valued customer! Enjoy 20% off your next purchase with code THANKS20. Valid this week only.',
                'recipients' => json_encode([
                    '+1234567810', '+1234567811', '+1234567812', '+1234567813', '+1234567814',
                    '+1234567815', '+1234567816', '+1234567817', '+1234567818', '+1234567819',
                    '+1234567820', '+1234567821', '+1234567822', '+1234567823', '+1234567824'
                ]),
                'status' => 'scheduled',
                'scheduled_at' => Carbon::now()->addDays(1),
                'started_at' => null,
                'completed_at' => null,
                'template_id' => null,
                'driver' => 'twilio',
                'from_number' => '+1987654323',
                'total_recipients' => 15,
                'sent_count' => 0,
                'failed_count' => 0,
                'settings' => json_encode([
                    'send_rate' => 8,
                    'retry_failed' => true,
                    'track_clicks' => true
                ]),
                'created_by' => 1,
            ],
            [
                'name' => 'Holiday Season Kickoff',
                'message' => '🎄 Holiday season is here! Get ready with our special holiday deals. Early bird gets 30% off!',
                'recipients' => json_encode([
                    '+1234567830', '+1234567831', '+1234567832'
                ]),
                'status' => 'draft',
                'scheduled_at' => null,
                'started_at' => null,
                'completed_at' => null,
                'template_id' => null,
                'driver' => 'plivo',
                'from_number' => '+1987654324',
                'total_recipients' => 3,
                'sent_count' => 0,
                'failed_count' => 0,
                'settings' => json_encode([
                    'send_rate' => 12,
                    'retry_failed' => false,
                    'track_clicks' => true
                ]),
                'created_by' => 1,
            ],
        ];
        
        foreach ($campaigns as $campaign) {
            SmsCampaign::create($campaign);
        }
        
        $this->command->info('Created ' . count($campaigns) . ' SMS campaigns.');
    }
    
    /**
     * Seed SMS logs
     * 
     * Creates realistic SMS log entries showing sent, failed, and pending messages
     * across different drivers and campaigns.
     */
    private function seedSmsLogs(): void
    {
        $drivers = ['twilio', 'vonage', 'plivo', 'infobip', 'messagebird'];
        $statuses = ['sent', 'failed', 'pending'];
        $phoneNumbers = [
            '+1234567890', '+1234567891', '+1234567892', '+1234567893', '+1234567894',
            '+1234567895', '+1234567896', '+1234567897', '+1234567898', '+1234567899',
            '+1234567800', '+1234567801', '+1234567802', '+1234567803', '+1234567804'
        ];
        
        $logs = [];
        
        // Generate logs for Black Friday campaign (completed)
        for ($i = 0; $i < 10; $i++) {
            $status = $i < 9 ? 'sent' : 'failed'; // 9 sent, 1 failed
            $logs[] = [
                'to' => $phoneNumbers[$i],
                'from' => '+1987654321',
                'body' => '🛍️ BLACK FRIDAY MEGA SALE! Up to 70% OFF everything! Shop now before items sell out: https://shop.example.com/blackfriday',
                'driver' => 'twilio',
                'status' => $status,
                'response' => json_encode([
                    'message_id' => 'tw_' . uniqid(),
                    'status' => $status,
                    'price' => '0.0075',
                    'price_unit' => 'USD'
                ]),
                'sent_at' => $status === 'sent' ? Carbon::now()->subDays(2)->addMinutes(rand(5, 65)) : null,
                'campaign_id' => 1,
                'template_id' => 3,
                'cost' => $status === 'sent' ? 0.0075 : null,
                'message_id' => $status === 'sent' ? 'tw_' . uniqid() : null,
                'error_message' => $status === 'failed' ? 'Invalid phone number format' : null,
                'created_at' => Carbon::now()->subDays(2)->addMinutes(rand(5, 65)),
                'updated_at' => Carbon::now()->subDays(2)->addMinutes(rand(5, 65)),
            ];
        }
        
        // Generate logs for Summer Collection campaign (running)
        for ($i = 0; $i < 3; $i++) {
            $logs[] = [
                'to' => '+123456780' . $i,
                'from' => '+1987654322',
                'body' => '☀️ NEW ARRIVALS! Check out our stunning Summer Collection. Be the first to shop: https://shop.example.com/summer',
                'driver' => 'vonage',
                'status' => 'sent',
                'response' => json_encode([
                    'message_id' => 'vn_' . uniqid(),
                    'status' => 'delivered',
                    'price' => '0.0080'
                ]),
                'sent_at' => Carbon::now()->subHours(2)->addMinutes(rand(10, 120)),
                'campaign_id' => 2,
                'template_id' => null,
                'cost' => 0.0080,
                'message_id' => 'vn_' . uniqid(),
                'error_message' => null,
                'created_at' => Carbon::now()->subHours(2)->addMinutes(rand(10, 120)),
                'updated_at' => Carbon::now()->subHours(2)->addMinutes(rand(10, 120)),
            ];
        }
        
        // Generate some individual SMS logs (not part of campaigns)
        $individualMessages = [
            'Welcome to our service! Thanks for signing up.',
            'Your order #12345 has been confirmed. Thank you!',
            'Reminder: Your appointment is tomorrow at 2 PM.',
            'Your package has been shipped. Tracking: ABC123',
            'Thank you for your purchase! Please rate your experience.',
        ];
        
        for ($i = 0; $i < 15; $i++) {
            $driver = $drivers[array_rand($drivers)];
            $status = $statuses[array_rand($statuses)];
            $phoneNumber = $phoneNumbers[array_rand($phoneNumbers)];
            $message = $individualMessages[array_rand($individualMessages)];
            
            $logs[] = [
                'to' => $phoneNumber,
                'from' => '+1987654320',
                'body' => $message,
                'driver' => $driver,
                'status' => $status,
                'response' => $status !== 'pending' ? json_encode([
                    'message_id' => strtolower(substr($driver, 0, 2)) . '_' . uniqid(),
                    'status' => $status === 'sent' ? 'delivered' : 'failed',
                    'price' => $status === 'sent' ? number_format(rand(50, 120) / 10000, 4) : null
                ]) : null,
                'sent_at' => $status === 'sent' ? Carbon::now()->subDays(rand(1, 7))->addMinutes(rand(0, 1440)) : null,
                'campaign_id' => null,
                'template_id' => rand(1, 8),
                'cost' => $status === 'sent' ? number_format(rand(50, 120) / 10000, 4) : null,
                'message_id' => $status === 'sent' ? strtolower(substr($driver, 0, 2)) . '_' . uniqid() : null,
                'error_message' => $status === 'failed' ? 'Network timeout' : null,
                'created_at' => Carbon::now()->subDays(rand(1, 7))->addMinutes(rand(0, 1440)),
                'updated_at' => Carbon::now()->subDays(rand(1, 7))->addMinutes(rand(0, 1440)),
            ];
        }
        
        foreach ($logs as $log) {
            SmsLog::create($log);
        }
        
        $this->command->info('Created ' . count($logs) . ' SMS log entries.');
    }
    
    /**
     * Seed SMS autoresponders
     * 
     * Creates automated response systems for different trigger types
     * including keyword-based, incoming SMS, and webhook triggers.
     */
    private function seedSmsAutoresponders(): void
    {
        $autoresponders = [
            [
                'name' => 'STOP Keyword Handler',
                'trigger_type' => 'keyword',
                'trigger_value' => json_encode(['keywords' => ['STOP', 'UNSUBSCRIBE', 'QUIT']]),
                'response_message' => 'You have been unsubscribed from our SMS list. You will no longer receive promotional messages.',
                'template_id' => null,
                'is_active' => true,
                'delay_minutes' => 0,
                'max_triggers_per_number' => 1,
                'conditions' => json_encode(['time_restriction' => false]),
                'settings' => json_encode(['auto_unsubscribe' => true, 'send_confirmation' => true]),
            ],
            [
                'name' => 'INFO Keyword Response',
                'trigger_type' => 'keyword',
                'trigger_value' => json_encode(['keywords' => ['INFO', 'HELP', 'SUPPORT']]),
                'response_message' => 'For support, visit https://help.example.com or call 1-800-HELP. Business hours: Mon-Fri 9AM-6PM EST.',
                'template_id' => null,
                'is_active' => true,
                'delay_minutes' => 0,
                'max_triggers_per_number' => 3,
                'conditions' => json_encode(['business_hours_only' => true]),
                'settings' => json_encode(['escalate_to_human' => false]),
            ],
            [
                'name' => 'Welcome New Subscriber',
                'trigger_type' => 'incoming_sms',
                'trigger_value' => json_encode(['first_message' => true]),
                'response_message' => null,
                'template_id' => 1, // Welcome message template
                'is_active' => true,
                'delay_minutes' => 2,
                'max_triggers_per_number' => 1,
                'conditions' => json_encode(['new_number_only' => true]),
                'settings' => json_encode(['send_welcome_series' => true]),
            ],
            [
                'name' => 'Birthday Offer Trigger',
                'trigger_type' => 'webhook',
                'trigger_value' => json_encode(['webhook_url' => 'https://api.example.com/birthday-webhook']),
                'response_message' => null,
                'template_id' => 6, // Birthday offer template
                'is_active' => true,
                'delay_minutes' => 60, // Send 1 hour after webhook
                'max_triggers_per_number' => 1,
                'conditions' => json_encode(['annual_limit' => 1]),
                'settings' => json_encode(['personalize' => true, 'track_redemption' => true]),
            ],
            [
                'name' => 'Cart Abandonment Recovery',
                'trigger_type' => 'webhook',
                'trigger_value' => json_encode(['webhook_url' => 'https://api.example.com/cart-abandoned']),
                'response_message' => null,
                'template_id' => 5, // Abandoned cart template
                'is_active' => true,
                'delay_minutes' => 1440, // Send after 24 hours
                'max_triggers_per_number' => 2,
                'conditions' => json_encode(['cart_value_min' => 50]),
                'settings' => json_encode(['include_discount' => true, 'free_shipping' => true]),
            ],
        ];
        
        foreach ($autoresponders as $autoresponder) {
            SmsAutoresponder::create($autoresponder);
        }
        
        $this->command->info('Created ' . count($autoresponders) . ' SMS autoresponders.');
    }
    
    /**
     * Seed SMS triggers
     * 
     * Creates trigger records showing when autoresponders were activated
     * by customer interactions.
     */
    private function seedSmsTriggers(): void
    {
        $phoneNumbers = [
            '+1234567890', '+1234567891', '+1234567892', '+1234567893', '+1234567894'
        ];
        
        $triggers = [];
        
        // STOP keyword triggers
        for ($i = 0; $i < 3; $i++) {
            $triggers[] = [
                'autoresponder_id' => 1, // STOP keyword handler
                'phone_number' => $phoneNumbers[$i],
                'trigger_type' => 'keyword',
                'trigger_data' => json_encode(['keyword' => 'STOP', 'original_message' => 'STOP']),
                'response_sent' => true,
                'response_message_id' => 'resp_' . uniqid(),
                'processed_at' => Carbon::now()->subDays(rand(1, 5)),
                'error_message' => null,
                'created_at' => Carbon::now()->subDays(rand(1, 5)),
                'updated_at' => Carbon::now()->subDays(rand(1, 5)),
            ];
        }
        
        // INFO keyword triggers
        for ($i = 0; $i < 5; $i++) {
            $keywords = ['INFO', 'HELP', 'SUPPORT'];
            $triggers[] = [
                'autoresponder_id' => 2, // INFO keyword response
                'phone_number' => $phoneNumbers[array_rand($phoneNumbers)],
                'trigger_type' => 'keyword',
                'trigger_data' => json_encode([
                    'keyword' => $keywords[array_rand($keywords)],
                    'original_message' => $keywords[array_rand($keywords)]
                ]),
                'response_sent' => true,
                'response_message_id' => 'resp_' . uniqid(),
                'processed_at' => Carbon::now()->subDays(rand(1, 3)),
                'error_message' => null,
                'created_at' => Carbon::now()->subDays(rand(1, 3)),
                'updated_at' => Carbon::now()->subDays(rand(1, 3)),
            ];
        }
        
        // Welcome new subscriber triggers
        for ($i = 0; $i < 4; $i++) {
            $triggers[] = [
                'autoresponder_id' => 3, // Welcome new subscriber
                'phone_number' => $phoneNumbers[$i],
                'trigger_type' => 'incoming_sms',
                'trigger_data' => json_encode(['first_message' => true, 'message' => 'Hi there!']),
                'response_sent' => true,
                'response_message_id' => 'resp_' . uniqid(),
                'processed_at' => Carbon::now()->subDays(rand(1, 7))->addMinutes(2),
                'error_message' => null,
                'created_at' => Carbon::now()->subDays(rand(1, 7)),
                'updated_at' => Carbon::now()->subDays(rand(1, 7))->addMinutes(2),
            ];
        }
        
        // Birthday offer triggers
        for ($i = 0; $i < 2; $i++) {
            $triggers[] = [
                'autoresponder_id' => 4, // Birthday offer trigger
                'phone_number' => $phoneNumbers[$i],
                'trigger_type' => 'webhook',
                'trigger_data' => json_encode([
                    'webhook_data' => ['customer_id' => 'cust_' . ($i + 1), 'birthday' => '2024-01-15']
                ]),
                'response_sent' => true,
                'response_message_id' => 'resp_' . uniqid(),
                'processed_at' => Carbon::now()->subDays(rand(1, 30))->addHours(1),
                'error_message' => null,
                'created_at' => Carbon::now()->subDays(rand(1, 30)),
                'updated_at' => Carbon::now()->subDays(rand(1, 30))->addHours(1),
            ];
        }
        
        // Cart abandonment triggers (some failed)
        for ($i = 0; $i < 3; $i++) {
            $responseSent = $i < 2; // 2 successful, 1 failed
            $triggers[] = [
                'autoresponder_id' => 5, // Cart abandonment recovery
                'phone_number' => $phoneNumbers[$i],
                'trigger_type' => 'webhook',
                'trigger_data' => json_encode([
                    'webhook_data' => [
                        'cart_id' => 'cart_' . ($i + 1),
                        'cart_value' => rand(50, 200),
                        'items' => rand(1, 5)
                    ]
                ]),
                'response_sent' => $responseSent,
                'response_message_id' => $responseSent ? 'resp_' . uniqid() : null,
                'processed_at' => $responseSent ? Carbon::now()->subDays(rand(1, 10))->addHours(24) : null,
                'error_message' => !$responseSent ? 'SMS delivery failed - invalid number' : null,
                'created_at' => Carbon::now()->subDays(rand(1, 10)),
                'updated_at' => Carbon::now()->subDays(rand(1, 10))->addHours(24),
            ];
        }
        
        foreach ($triggers as $trigger) {
            SmsTrigger::create($trigger);
        }
        
        $this->command->info('Created ' . count($triggers) . ' SMS triggers.');
    }
    
    /**
     * Seed SMS automation logs
     * 
     * Creates detailed logs of automation executions showing performance
     * metrics and execution details for monitoring and optimization.
     */
    private function seedSmsAutomationLogs(): void
    {
        $phoneNumbers = [
            '+1234567890', '+1234567891', '+1234567892', '+1234567893', '+1234567894'
        ];
        $drivers = ['twilio', 'vonage', 'plivo'];
        
        $automationLogs = [];
        
        // Logs for STOP keyword automation
        for ($i = 0; $i < 3; $i++) {
            $automationLogs[] = [
                'autoresponder_id' => 1,
                'trigger_id' => $i + 1, // Corresponding trigger IDs
                'phone_number' => $phoneNumbers[$i],
                'trigger_type' => 'keyword',
                'trigger_value' => 'STOP',
                'response_message' => 'You have been unsubscribed from our SMS list. You will no longer receive promotional messages.',
                'status' => 'success',
                'message_id' => 'auto_' . uniqid(),
                'driver_used' => $drivers[array_rand($drivers)],
                'execution_time_ms' => rand(150, 500),
                'error_message' => null,
                'context_data' => json_encode([
                    'original_message' => 'STOP',
                    'unsubscribed' => true,
                    'previous_subscription_status' => 'active'
                ]),
                'created_at' => Carbon::now()->subDays(rand(1, 5)),
                'updated_at' => Carbon::now()->subDays(rand(1, 5)),
            ];
        }
        
        // Logs for INFO keyword automation
        for ($i = 0; $i < 5; $i++) {
            $automationLogs[] = [
                'autoresponder_id' => 2,
                'trigger_id' => $i + 4, // Trigger IDs 4-8
                'phone_number' => $phoneNumbers[array_rand($phoneNumbers)],
                'trigger_type' => 'keyword',
                'trigger_value' => ['INFO', 'HELP', 'SUPPORT'][array_rand(['INFO', 'HELP', 'SUPPORT'])],
                'response_message' => 'For support, visit https://help.example.com or call 1-800-HELP. Business hours: Mon-Fri 9AM-6PM EST.',
                'status' => 'success',
                'message_id' => 'auto_' . uniqid(),
                'driver_used' => $drivers[array_rand($drivers)],
                'execution_time_ms' => rand(200, 600),
                'error_message' => null,
                'context_data' => json_encode([
                    'business_hours' => true,
                    'support_ticket_created' => false,
                    'previous_help_requests' => rand(0, 2)
                ]),
                'created_at' => Carbon::now()->subDays(rand(1, 3)),
                'updated_at' => Carbon::now()->subDays(rand(1, 3)),
            ];
        }
        
        // Logs for welcome message automation
        for ($i = 0; $i < 4; $i++) {
            $automationLogs[] = [
                'autoresponder_id' => 3,
                'trigger_id' => $i + 9, // Trigger IDs 9-12
                'phone_number' => $phoneNumbers[$i],
                'trigger_type' => 'incoming_sms',
                'trigger_value' => 'first_message',
                'response_message' => 'Welcome to Example Company! Thanks for joining us. Use code WELCOME10 for 10% off your first order. Reply STOP to opt out.',
                'status' => 'success',
                'message_id' => 'auto_' . uniqid(),
                'driver_used' => $drivers[array_rand($drivers)],
                'execution_time_ms' => rand(300, 800),
                'error_message' => null,
                'context_data' => json_encode([
                    'new_subscriber' => true,
                    'welcome_series_started' => true,
                    'discount_code_generated' => 'WELCOME10',
                    'subscription_source' => 'website_signup'
                ]),
                'created_at' => Carbon::now()->subDays(rand(1, 7)),
                'updated_at' => Carbon::now()->subDays(rand(1, 7)),
            ];
        }
        
        // Logs for birthday offer automation
        for ($i = 0; $i < 2; $i++) {
            $automationLogs[] = [
                'autoresponder_id' => 4,
                'trigger_id' => $i + 13, // Trigger IDs 13-14
                'phone_number' => $phoneNumbers[$i],
                'trigger_type' => 'webhook',
                'trigger_value' => 'birthday_webhook',
                'response_message' => 'Happy Birthday John! 🎉 Enjoy 25% off as our gift to you. Use code: BIRTHDAY25. Valid until 2024-02-15.',
                'status' => 'success',
                'message_id' => 'auto_' . uniqid(),
                'driver_used' => $drivers[array_rand($drivers)],
                'execution_time_ms' => rand(400, 1000),
                'error_message' => null,
                'context_data' => json_encode([
                    'customer_name' => 'John',
                    'birthday_date' => '2024-01-15',
                    'discount_code' => 'BIRTHDAY25',
                    'discount_percentage' => 25,
                    'expiry_date' => '2024-02-15'
                ]),
                'created_at' => Carbon::now()->subDays(rand(1, 30)),
                'updated_at' => Carbon::now()->subDays(rand(1, 30)),
            ];
        }
        
        // Logs for cart abandonment automation (including failures)
        for ($i = 0; $i < 3; $i++) {
            $status = $i < 2 ? 'success' : 'failed';
            $automationLogs[] = [
                'autoresponder_id' => 5,
                'trigger_id' => $i + 15, // Trigger IDs 15-17
                'phone_number' => $phoneNumbers[$i],
                'trigger_type' => 'webhook',
                'trigger_value' => 'cart_abandoned',
                'response_message' => $status === 'success' ? 'You left 3 items in your cart! Complete your purchase now and get FREE shipping: https://shop.example.com/cart/abc123' : null,
                'status' => $status,
                'message_id' => $status === 'success' ? 'auto_' . uniqid() : null,
                'driver_used' => $status === 'success' ? $drivers[array_rand($drivers)] : 'twilio',
                'execution_time_ms' => $status === 'success' ? rand(500, 1200) : rand(100, 300),
                'error_message' => $status === 'failed' ? 'SMS delivery failed - invalid phone number format' : null,
                'context_data' => json_encode([
                    'cart_id' => 'cart_' . ($i + 1),
                    'cart_value' => rand(50, 200),
                    'items_count' => rand(1, 5),
                    'abandonment_time' => '24_hours',
                    'free_shipping_applied' => $status === 'success',
                    'discount_applied' => false
                ]),
                'created_at' => Carbon::now()->subDays(rand(1, 10)),
                'updated_at' => Carbon::now()->subDays(rand(1, 10)),
            ];
        }
        
        foreach ($automationLogs as $log) {
            SmsAutomationLog::create($log);
        }
        
        $this->command->info('Created ' . count($automationLogs) . ' SMS automation log entries.');
    }
}