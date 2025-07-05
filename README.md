# Multi-SMS Laravel Package

A comprehensive Laravel package for sending SMS messages through multiple providers with fallback support, logging, and advanced features.

## Features

- **Multiple SMS Drivers**: Support for Twilio, Vonage (Nexmo), Plivo, Infobip, MessageBird, Viber, and WhatsApp
- **Fallback Support**: Automatic fallback to alternative drivers if primary fails
- **Comprehensive Logging**: Track all SMS activities with detailed logs
- **Bulk SMS Sending**: Send messages to multiple recipients
- **Queue Support**: Process SMS messages in background queues
- **Rate Limiting**: Control SMS sending rates to prevent spam
- **Cost Tracking**: Monitor SMS costs across different providers
- **Laravel Integration**: Seamless integration with Laravel's service container
- **Package Discovery**: Automatic service provider and facade registration

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
SMS_DRIVER=twilio

# Twilio Configuration
TWILIO_ACCOUNT_SID=your_twilio_sid
TWILIO_AUTH_TOKEN=your_twilio_token
TWILIO_FROM=+1234567890

# Vonage Configuration
VONAGE_API_KEY=your_vonage_key
VONAGE_API_SECRET=your_vonage_secret
VONAGE_FROM=YourBrand

# Plivo Configuration
PLIVO_AUTH_ID=your_plivo_auth_id
PLIVO_AUTH_TOKEN=your_plivo_auth_token
PLIVO_FROM=+1234567890

# Infobip Configuration
INFOBIP_API_KEY=your_infobip_api_key
INFOBIP_BASE_URL=https://api.infobip.com
INFOBIP_FROM=+1234567890

# MessageBird Configuration
MESSAGEBIRD_ACCESS_KEY=your_messagebird_access_key
MESSAGEBIRD_FROM=+1234567890

# Viber Configuration
VIBER_AUTH_TOKEN=your_viber_auth_token
VIBER_SENDER_NAME=YourBrand

# WhatsApp Configuration
WHATSAPP_ACCESS_TOKEN=your_whatsapp_access_token
WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id
WHATSAPP_FROM=+1234567890

# Logging Configuration
SMS_LOGGING_ENABLED=true
SMS_LOG_SUCCESS=true
SMS_LOG_FAILURES=true

# Default Sender
SMS_DEFAULT_FROM=+1234567890
```

## Usage

### Basic SMS Sending

#### Using the Facade (Simple API)

```php
use MultiSms\Facades\Sms;

// Send a simple SMS
$result = Sms::send('+1234567890', 'Hello, this is a test message!');

if ($result['success']) {
    echo "SMS sent successfully! Message ID: " . $result['message_id'];
} else {
    echo "Failed to send SMS: " . $result['error'];
}
```

#### Using Fluent Interface

```php
use MultiSms\Facades\Sms;

// Send using fluent interface
$result = Sms::to('+1234567890')
    ->message('Hello, this is a test message!')
    ->send();

if ($result['success']) {
    echo "SMS sent successfully! Message ID: " . $result['message_id'];
} else {
    echo "Failed to send SMS";
}
```

#### Using a Specific Driver

```php
// Send using Twilio
$result = Sms::driver('twilio')
    ->to('+1234567890')
    ->message('Hello from Twilio!')
    ->from('+0987654321')
    ->send();

// Send using Vonage
$result = Sms::driver('vonage')
    ->to('+1234567890')
    ->message('Hello from Vonage!')
    ->send();
```

#### Bulk SMS Sending

```php
$recipients = ['+1234567890', '+0987654321', '+1122334455'];

// Using simple API
$results = Sms::sendBulk($recipients, 'Bulk message to all recipients');

// Using fluent interface
$results = Sms::to($recipients)
    ->message('Bulk message to all recipients')
    ->send();

foreach ($results as $result) {
    if ($result['success']) {
        echo "SMS sent: {$result['message_id']}\n";
    } else {
        echo "Failed to send: {$result['error']}\n";
    }
}
```

### Direct Service Usage

```php
use MultiSms\Services\SmsService;

// Inject the service
public function sendSms(SmsService $smsService)
{
    $result = $smsService->send(
        '+1234567890',
        'Hello World!',
        'twilio', // optional driver
        '+0987654321' // optional from number
    );
    
    return $result;
}
```

### Available Drivers

The package supports the following SMS drivers:

- **twilio**: Twilio SMS API
- **vonage**: Vonage (formerly Nexmo) SMS API
- **plivo**: Plivo SMS API
- **infobip**: Infobip SMS API
- **messagebird**: MessageBird SMS API
- **viber**: Viber Business Messages
- **whatsapp**: WhatsApp Business API

### Fallback Configuration

The package automatically tries fallback drivers if the primary driver fails. Configure fallback priority in `config/multi-sms.php`:

```php
'fallback_priority' => [
    'twilio',
    'vonage',
    'plivo',
    'infobip',
    'messagebird',
],
```

### Logging

All SMS activities are logged to the `sms_logs` table when logging is enabled. You can query logs using the `SmsLog` model:

```php
use MultiSms\Models\SmsLog;

// Get all sent messages
$sentMessages = SmsLog::where('status', 'sent')->get();

// Get failed messages
$failedMessages = SmsLog::where('status', 'failed')->get();

// Get messages by driver
$twilioMessages = SmsLog::where('driver', 'twilio')->get();
```

### Error Handling

```php
use MultiSms\Exceptions\SmsException;

try {
    $result = Sms::to('+1234567890')
        ->message('Test message')
        ->send();
        
    if (!$result['success']) {
        // Handle SMS sending failure
        Log::error('SMS failed: ' . ($result['error'] ?? 'Unknown error'));
    }
} catch (SmsException $e) {
    // Handle SMS-specific exceptions
    Log::error('SMS exception: ' . $e->getMessage());
} catch (\Exception $e) {
    // Handle general exceptions
    Log::error('General exception: ' . $e->getMessage());
}
```

### Rate Limiting

Configure rate limiting in your config:

```php
'rate_limiting' => [
    'enabled' => env('SMS_RATE_LIMITING_ENABLED', false),
    'max_attempts' => env('SMS_MAX_ATTEMPTS', 60),
    'decay_minutes' => env('SMS_DECAY_MINUTES', 1),
],
```

### Retry Configuration

Configure retry attempts for failed messages:

```php
'retry' => [
    'max_attempts' => env('SMS_RETRY_MAX_ATTEMPTS', 3),
    'delay_seconds' => env('SMS_RETRY_DELAY_SECONDS', 60),
],
```

## Testing

For testing purposes, you can use the log driver or create a fake driver:

```php
// In your test environment
config(['multi-sms.default' => 'log']);

// SMS will be logged instead of sent
$result = Sms::to('+1234567890')
    ->message('Test message')
    ->send();
```

## API Methods

### SmsService Methods

- `send(string $to, string $message, ?string $driver = null, ?string $from = null): array`
- `sendBulk(array $recipients, string $message, ?string $driver = null, ?string $from = null): array`
- `to(string|array $to): SmsBuilder` - Returns fluent interface
- `driver(string $driver): SmsBuilder` - Returns fluent interface
- `getAvailableDrivers(): array`
- `isDriverConfigured(string $driver): bool`

### SmsBuilder Methods (Fluent Interface)

- `to(string|array $to): self`
- `message(string $message): self`
- `driver(string $driver): self`
- `from(string $from): self`
- `send(): array`
- `reset(): self`

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).

## Support

If you encounter any issues or have questions, please [open an issue](https://github.com/your-vendor/multi-sms/issues) on GitHub.