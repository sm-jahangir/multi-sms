# Multi-SMS Laravel Package

A comprehensive Laravel package for sending SMS messages through multiple providers with advanced features like campaigns, templates, autoresponders, and detailed analytics.

## Features

- **Multiple SMS Drivers**: Support for Twilio, Nexmo/Vonage, AWS SNS, and custom drivers
- **SMS Templates**: Create reusable message templates with variable substitution
- **SMS Campaigns**: Send bulk SMS messages to multiple recipients
- **Autoresponders**: Automated SMS responses based on triggers (keywords, incoming SMS, missed calls, webhooks)
- **Comprehensive Logging**: Track all SMS activities with detailed logs
- **Analytics & Reporting**: Get insights into SMS performance and statistics
- **Queue Support**: Process SMS messages in background queues
- **Rate Limiting**: Control SMS sending rates to prevent spam
- **Cost Tracking**: Monitor SMS costs across different providers
- **Artisan Commands**: Command-line tools for SMS operations
- **RESTful API**: Complete API endpoints for all functionality

## Installation

1. Install the package via Composer:

```bash
composer require your-vendor/multi-sms
```

2. Publish the configuration file:

```bash
php artisan vendor:publish --provider="MultiSms\MultiSmsServiceProvider" --tag="config"
```

3. Publish and run the migrations:

```bash
php artisan vendor:publish --provider="MultiSms\MultiSmsServiceProvider" --tag="migrations"
php artisan migrate
```

4. Configure your SMS drivers in the published config file or environment variables.

## Configuration

### Environment Variables

Add the following to your `.env` file:

```env
# Default SMS Driver
SMS_DEFAULT_DRIVER=twilio

# Twilio Configuration
TWILIO_SID=your_twilio_sid
TWILIO_TOKEN=your_twilio_token
TWILIO_FROM=+1234567890

# Nexmo/Vonage Configuration
NEXMO_KEY=your_nexmo_key
NEXMO_SECRET=your_nexmo_secret
NEXMO_FROM=YourBrand

# AWS SNS Configuration
AWS_ACCESS_KEY_ID=your_aws_key
AWS_SECRET_ACCESS_KEY=your_aws_secret
AWS_DEFAULT_REGION=us-east-1
AWS_SNS_FROM=+1234567890

# Log Configuration
SMS_LOG_ENABLED=true
SMS_LOG_FAILED_ONLY=false
```

### Driver Configuration

The package supports multiple SMS drivers. Configure them in `config/multi-sms.php`:

```php
'drivers' => [
    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_TOKEN'),
        'from' => env('TWILIO_FROM'),
    ],
    'nexmo' => [
        'key' => env('NEXMO_KEY'),
        'secret' => env('NEXMO_SECRET'),
        'from' => env('NEXMO_FROM'),
    ],
    'aws' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'from' => env('AWS_SNS_FROM'),
    ],
],
```

## Usage

### Basic SMS Sending

#### Using the Facade

```php
use MultiSms\Facades\MultiSms;

// Send a simple SMS
$result = MultiSms::to('+1234567890')
    ->message('Hello, this is a test message!')
    ->send();

if ($result['success']) {
    echo "SMS sent successfully! Message ID: " . $result['message_id'];
} else {
    echo "Failed to send SMS: " . $result['error'];
}
```

#### Using a Specific Driver

```php
// Send using Twilio
$result = MultiSms::driver('twilio')
    ->to('+1234567890')
    ->message('Hello from Twilio!')
    ->from('+0987654321')
    ->send();

// Send using Nexmo
$result = MultiSms::driver('nexmo')
    ->to('+1234567890')
    ->message('Hello from Nexmo!')
    ->send();
```

#### Bulk SMS Sending

```php
$recipients = ['+1234567890', '+0987654321', '+1122334455'];

$results = MultiSms::to($recipients)
    ->message('Bulk message to all recipients')
    ->send();

foreach ($results as $result) {
    if ($result['success']) {
        echo "SMS sent to {$result['to']}: {$result['message_id']}\n";
    } else {
        echo "Failed to send to {$result['to']}: {$result['error']}\n";
    }
}
```

### SMS Templates

#### Creating Templates

```php
use MultiSms\Models\SmsTemplate;

$template = SmsTemplate::create([
    'key' => 'welcome_message',
    'name' => 'Welcome Message',
    'body' => 'Welcome {{name}}! Your account has been created successfully. Your username is {{username}}.',
    'variables' => ['name', 'username'],
    'is_active' => true,
    'description' => 'Welcome message for new users'
]);
```

#### Using Templates

```php
// Send using template key
$result = MultiSms::to('+1234567890')
    ->template('welcome_message', [
        'name' => 'John Doe',
        'username' => 'johndoe123'
    ])
    ->send();

// Send using template model
$template = SmsTemplate::where('key', 'welcome_message')->first();
$result = MultiSms::to('+1234567890')
    ->template($template, [
        'name' => 'Jane Smith',
        'username' => 'janesmith456'
    ])
    ->send();
```

### SMS Campaigns

#### Creating and Running Campaigns

```php
use MultiSms\Models\SmsCampaign;

// Create a campaign
$campaign = SmsCampaign::create([
    'name' => 'Product Launch Announcement',
    'message' => 'Exciting news! Our new product is now available. Visit our website to learn more.',
    'recipients' => ['+1234567890', '+0987654321', '+1122334455'],
    'status' => 'draft',
    'scheduled_at' => now()->addHours(2),
    'driver' => 'twilio',
    'from_number' => '+1234567890'
]);

// Start the campaign
$campaign->update(['status' => 'scheduled']);

// Or start immediately
$campaign->update([
    'status' => 'running',
    'started_at' => now()
]);
```

#### Using Templates in Campaigns

```php
$campaign = SmsCampaign::create([
    'name' => 'Personalized Offers',
    'template_id' => $template->id,
    'recipients' => [
        '+1234567890' => ['name' => 'John', 'offer' => '20% off'],
        '+0987654321' => ['name' => 'Jane', 'offer' => '15% off']
    ],
    'status' => 'scheduled',
    'scheduled_at' => now()->addMinutes(30)
]);
```

### Autoresponders

#### Keyword-Based Autoresponders

```php
use MultiSms\Models\SmsAutoresponder;

// Create a keyword autoresponder
$autoresponder = SmsAutoresponder::create([
    'name' => 'Info Request',
    'trigger_type' => 'keyword',
    'trigger_value' => 'INFO',
    'response_message' => 'Thank you for your interest! Visit our website at example.com for more information.',
    'is_active' => true,
    'delay_minutes' => 0,
    'max_triggers_per_number' => 3
]);
```

#### Template-Based Autoresponders

```php
$autoresponder = SmsAutoresponder::create([
    'name' => 'Welcome Autoresponder',
    'trigger_type' => 'incoming_sms',
    'trigger_value' => 'WELCOME',
    'template_id' => $template->id,
    'is_active' => true,
    'conditions' => [
        'time_range' => ['start' => '09:00', 'end' => '17:00'],
        'days_of_week' => [1, 2, 3, 4, 5] // Monday to Friday
    ]
]);
```

#### Webhook Autoresponders

```php
$autoresponder = SmsAutoresponder::create([
    'name' => 'Order Confirmation',
    'trigger_type' => 'webhook',
    'trigger_value' => 'order_placed',
    'response_message' => 'Your order #{{order_id}} has been confirmed. Total: ${{total}}',
    'is_active' => true,
    'settings' => [
        'webhook_secret' => 'your_webhook_secret',
        'required_fields' => ['order_id', 'total', 'phone_number']
    ]
]);
```

### Analytics and Reporting

#### Getting SMS Statistics

```php
use MultiSms\Models\SmsLog;

// Get today's statistics
$todayStats = SmsLog::whereDate('created_at', today())
    ->selectRaw('
        COUNT(*) as total,
        SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent,
        SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
        SUM(cost) as total_cost
    ')
    ->first();

// Get statistics by driver
$driverStats = SmsLog::selectRaw('
        driver,
        COUNT(*) as total,
        SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent,
        AVG(cost) as avg_cost
    ')
    ->groupBy('driver')
    ->get();
```

#### Campaign Analytics

```php
$campaign = SmsCampaign::find(1);

// Get campaign statistics
$stats = [
    'total_recipients' => $campaign->total_recipients,
    'sent_count' => $campaign->sent_count,
    'failed_count' => $campaign->failed_count,
    'success_rate' => $campaign->sent_count / $campaign->total_recipients * 100,
    'total_cost' => $campaign->logs()->sum('cost')
];
```

### Artisan Commands

#### Send SMS Command

```bash
# Send a single SMS
php artisan sms:send "+1234567890" "Hello World!"

# Send using specific driver
php artisan sms:send "+1234567890" "Hello!" --driver=twilio

# Send using template
php artisan sms:send "+1234567890" --template=welcome_message --variables='{"name":"John"}'

# Bulk send from file
php artisan sms:send --file=recipients.txt "Bulk message"

# Dry run (preview only)
php artisan sms:send "+1234567890" "Test" --dry-run
```

#### Run Campaigns Command

```bash
# Process all scheduled campaigns
php artisan sms:campaigns

# Process specific campaign
php artisan sms:campaigns --campaign=1

# Force run (ignore schedule)
php artisan sms:campaigns --force

# Dry run
php artisan sms:campaigns --dry-run
```

### API Endpoints

The package provides comprehensive REST API endpoints:

#### SMS Operations

```bash
# Send single SMS
POST /api/multi-sms/send
{
    "to": "+1234567890",
    "message": "Hello World!",
    "driver": "twilio"
}

# Send bulk SMS
POST /api/multi-sms/send-bulk
{
    "recipients": ["+1234567890", "+0987654321"],
    "message": "Bulk message",
    "driver": "nexmo"
}

# Get SMS status
GET /api/multi-sms/status/{messageId}

# List available drivers
GET /api/multi-sms/drivers
```

#### Campaign Management

```bash
# List campaigns
GET /api/multi-sms/campaigns

# Create campaign
POST /api/multi-sms/campaigns
{
    "name": "New Campaign",
    "message": "Campaign message",
    "recipients": ["+1234567890"],
    "scheduled_at": "2024-01-01 10:00:00"
}

# Start campaign
POST /api/multi-sms/campaigns/{id}/start

# Get campaign statistics
GET /api/multi-sms/campaigns/{id}/stats
```

#### Template Management

```bash
# List templates
GET /api/multi-sms/templates

# Create template
POST /api/multi-sms/templates
{
    "key": "welcome",
    "name": "Welcome Message",
    "body": "Welcome {{name}}!",
    "variables": ["name"]
}

# Preview template
POST /api/multi-sms/templates/{id}/preview
{
    "variables": {"name": "John"}
}
```

#### Analytics

```bash
# Get SMS logs
GET /api/multi-sms/logs?status=sent&driver=twilio

# Get analytics
GET /api/multi-sms/analytics?period=last_7_days&driver=twilio

# Export logs
GET /api/multi-sms/logs/export?format=csv&status=sent
```

### Queue Integration

For better performance, SMS sending can be queued:

```php
// Queue SMS sending
$result = MultiSms::to('+1234567890')
    ->message('Queued message')
    ->queue();

// Queue with delay
$result = MultiSms::to('+1234567890')
    ->message('Delayed message')
    ->queue(now()->addMinutes(5));

// Queue on specific queue
$result = MultiSms::to('+1234567890')
    ->message('Priority message')
    ->onQueue('high-priority')
    ->queue();
```

### Error Handling

```php
try {
    $result = MultiSms::to('+1234567890')
        ->message('Test message')
        ->send();
        
    if (!$result['success']) {
        // Handle SMS sending failure
        Log::error('SMS failed: ' . $result['error']);
    }
} catch (\Exception $e) {
    // Handle exceptions
    Log::error('SMS exception: ' . $e->getMessage());
}
```

### Events

The package fires several events that you can listen to:

```php
// Listen for SMS sent event
Event::listen(\MultiSms\Events\SmsSent::class, function ($event) {
    Log::info('SMS sent to: ' . $event->to);
});

// Listen for SMS failed event
Event::listen(\MultiSms\Events\SmsFailed::class, function ($event) {
    Log::error('SMS failed to: ' . $event->to . ' - ' . $event->error);
});

// Listen for campaign completed event
Event::listen(\MultiSms\Events\CampaignCompleted::class, function ($event) {
    Log::info('Campaign completed: ' . $event->campaign->name);
});
```

### Testing

For testing purposes, you can use the log driver:

```php
// In your test environment
config(['multi-sms.default' => 'log']);

// SMS will be logged instead of sent
$result = MultiSms::to('+1234567890')
    ->message('Test message')
    ->send();
```

## Advanced Features

### Custom Drivers

You can create custom SMS drivers by implementing the `SmsDriverInterface`:

```php
use MultiSms\Contracts\SmsDriverInterface;

class CustomSmsDriver implements SmsDriverInterface
{
    public function send(string $to, string $message, array $options = []): array
    {
        // Implement your custom SMS sending logic
        return [
            'success' => true,
            'message_id' => 'custom_' . uniqid(),
            'cost' => 0.05
        ];
    }
    
    public function getStatus(string $messageId): array
    {
        // Implement status checking logic
        return ['status' => 'delivered'];
    }
}
```

### Rate Limiting

Configure rate limiting in your config:

```php
'rate_limiting' => [
    'enabled' => true,
    'max_per_minute' => 60,
    'max_per_hour' => 1000,
    'max_per_day' => 10000,
],
```

### Webhooks

Handle incoming webhooks for delivery reports:

```php
// In your routes file
Route::post('/sms/webhook/{driver}', [WebhookController::class, 'handle']);
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).

## Support

If you encounter any issues or have questions, please [open an issue](https://github.com/your-vendor/multi-sms/issues) on GitHub.