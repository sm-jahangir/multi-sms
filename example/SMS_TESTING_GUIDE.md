# SMS Marketing Testing Guide

This guide provides comprehensive instructions for testing the Multi-SMS package using the realistic seeder and test routes.

## Overview

The SMS Marketing package includes:
- **Realistic Seeder**: Populates database with sample SMS marketing data
- **Test Controller**: Organized testing functionality via `SmsTestController`
- **Test Routes**: Comprehensive API endpoints for testing all features
- **Sample Data**: Templates, campaigns, autoresponders, and logs

## Quick Start

### 1. Run the Seeder

First, populate your database with realistic test data:

```bash
# Via Artisan command
php artisan db:seed --class="MultiSms\Database\Seeders\SmsMarketingSeeder"

# Or via test route
GET /sms-test/run-seeder
```

### 2. View Available Test Routes

Get a complete list of available testing endpoints:

```bash
GET /sms-test/routes
```

## Test Routes Documentation

### Seeder Management

#### Run SMS Marketing Seeder
```
GET /sms-test/run-seeder
```
**Purpose**: Executes the SMS Marketing Seeder to populate database with test data

**Response**: 
- Seeder execution status
- Output from Artisan command
- Next steps for testing

---

### Template Testing

#### Get All SMS Templates
```
GET /sms-test/templates
```
**Purpose**: Retrieves all SMS templates with their variables and configuration

**Sample Response**:
```json
{
  "status": "success",
  "count": 12,
  "templates": [
    {
      "id": 1,
      "key": "welcome_message",
      "name": "Welcome Message",
      "body": "Welcome to {{company_name}}, {{customer_name}}! Thank you for joining us.",
      "variables": ["company_name", "customer_name"],
      "is_active": true
    }
  ]
}
```

#### Send SMS Using Template
```
GET /sms-test/send-template/{templateId}
```
**Purpose**: Sends SMS using a specific template with sample variable data

**Parameters**:
- `templateId`: ID of the template to use

**Features**:
- Automatically replaces template variables with realistic sample data
- Shows both original template and processed message
- Returns SMS sending result

---

### Campaign Testing

#### Get All SMS Campaigns
```
GET /sms-test/campaigns
```
**Purpose**: Retrieves all campaigns with statistics and performance metrics

**Sample Response**:
```json
{
  "status": "success",
  "count": 8,
  "campaigns": [
    {
      "id": 1,
      "name": "Summer Sale Campaign",
      "status": "completed",
      "total_recipients": 1000,
      "sent_count": 950,
      "failed_count": 50,
      "success_rate": 95.0,
      "driver": "twilio"
    }
  ]
}
```

#### Create and Execute Test Campaign
```
GET /sms-test/create-campaign
```
**Purpose**: Creates a new campaign with test data and immediately executes it

**Features**:
- Creates campaign with 3 test recipients
- Sends messages using configured SMS driver
- Creates log entries for each message
- Updates campaign statistics
- Returns detailed execution results

---

### Analytics and Reporting

#### Get Comprehensive SMS Analytics
```
GET /sms-test/logs/analytics
```
**Purpose**: Provides detailed analytics and performance metrics

**Analytics Include**:
- **Overview**: Total messages, success rates, costs
- **Driver Statistics**: Performance by SMS provider
- **Recent Activity**: Latest 10 SMS logs
- **Daily Statistics**: 7-day performance trend

**Sample Response**:
```json
{
  "analytics": {
    "overview": {
      "total_messages": 2500,
      "sent_messages": 2375,
      "failed_messages": 125,
      "overall_success_rate": 95.0,
      "total_cost": 18.75
    },
    "driver_statistics": [
      {
        "driver": "twilio",
        "total": 1500,
        "sent": 1425,
        "failed": 75,
        "success_rate": 95.0,
        "total_cost": 11.25
      }
    ]
  }
}
```

---

### Autoresponder Testing

#### Get All Autoresponders
```
GET /sms-test/autoresponders
```
**Purpose**: Retrieves all autoresponders with their configuration and statistics

**Response Includes**:
- Trigger types (keyword, incoming_sms, webhook)
- Response messages and templates
- Activity statistics
- Success rates

#### Trigger Autoresponder by Keyword
```
GET /sms-test/trigger-autoresponder/{keyword}
```
**Purpose**: Simulates triggering an autoresponder with a specific keyword

**Parameters**:
- `keyword`: The keyword to trigger (e.g., "STOP", "INFO", "HELP")

**Available Keywords** (from seeded data):
- `STOP`, `UNSUBSCRIBE` - Opt-out autoresponder
- `INFO`, `HELP` - Information autoresponder
- `START`, `SUBSCRIBE` - Opt-in autoresponder
- `PROMO`, `DEALS` - Promotional autoresponder

**Process**:
1. Finds matching autoresponder
2. Creates trigger record
3. Sends autoresponse SMS
4. Updates trigger status
5. Creates automation log

---

### Service Method Testing

#### Test SMS Service Methods
```
GET /sms-test/service-methods
```
**Purpose**: Tests various SMS service methods and configurations

**Tests Include**:
- Available SMS drivers
- Driver configuration status
- Default driver settings
- Fallback configuration

---

## Seeded Data Overview

The seeder creates realistic test data including:

### SMS Templates (12 templates)
- **Welcome Message**: Customer onboarding
- **Order Confirmation**: E-commerce confirmations
- **Appointment Reminder**: Healthcare/service reminders
- **Promotional Offer**: Marketing campaigns
- **Password Reset**: Security notifications
- **Delivery Update**: Shipping notifications
- **Payment Reminder**: Billing notifications
- **Survey Request**: Feedback collection
- **Event Invitation**: Event marketing
- **Cart Abandonment**: E-commerce recovery
- **Review Request**: Customer feedback
- **Account Alert**: Security notifications

### SMS Campaigns (8 campaigns)
- Various statuses: completed, running, scheduled, failed
- Different drivers: Twilio, Vonage, Plivo
- Realistic recipient counts and success rates
- Scheduled campaigns for future dates

### SMS Logs (50 entries)
- Mixed statuses: sent, failed, pending
- Various drivers and costs
- Realistic phone numbers and messages
- Associated with campaigns and templates

### Autoresponders (4 autoresponders)
- **Opt-out**: STOP, UNSUBSCRIBE keywords
- **Information**: INFO, HELP keywords
- **Opt-in**: START, SUBSCRIBE keywords
- **Promotional**: PROMO, DEALS keywords

### Triggers and Automation Logs
- 20 trigger records with various types
- 15 automation logs with execution details
- Realistic response times and status tracking

## Testing Workflow

### 1. Initial Setup
```bash
# Run seeder to populate data
GET /sms-test/run-seeder

# View available routes
GET /sms-test/routes
```

### 2. Template Testing
```bash
# View all templates
GET /sms-test/templates

# Test sending with template ID 1
GET /sms-test/send-template/1
```

### 3. Campaign Testing
```bash
# View existing campaigns
GET /sms-test/campaigns

# Create and execute new test campaign
GET /sms-test/create-campaign
```

### 4. Analytics Review
```bash
# View comprehensive analytics
GET /sms-test/logs/analytics
```

### 5. Autoresponder Testing
```bash
# View all autoresponders
GET /sms-test/autoresponders

# Test keyword triggers
GET /sms-test/trigger-autoresponder/STOP
GET /sms-test/trigger-autoresponder/INFO
GET /sms-test/trigger-autoresponder/PROMO
```

### 6. Service Validation
```bash
# Test service methods
GET /sms-test/service-methods
```

## Basic SMS Testing

In addition to the comprehensive test routes, you can also test basic SMS functionality:

```bash
# Test fluent interface
GET /test-sms

# Test simple API
GET /test-sms-simple

# Test bulk sending
GET /test-sms-bulk
```

## Error Handling

All test routes include comprehensive error handling:

- **404 Errors**: When templates/campaigns not found
- **500 Errors**: For service failures
- **Validation Errors**: For invalid parameters
- **SMS Errors**: When SMS sending fails

Example error response:
```json
{
  "status": "error",
  "message": "Failed to send SMS using template",
  "error": "Template not found"
}
```

## Performance Considerations

- **Rate Limiting**: Test routes respect SMS provider rate limits
- **Cost Management**: Uses test phone numbers to avoid charges
- **Batch Processing**: Campaign execution handles large recipient lists
- **Error Recovery**: Failed messages are logged for analysis

## Customization

You can customize the test data by:

1. **Modifying the Seeder**: Edit `SmsMarketingSeeder.php`
2. **Adding Test Routes**: Extend `SmsTestController.php`
3. **Custom Templates**: Add your own template variables
4. **Driver Configuration**: Test with different SMS providers

## Troubleshooting

### Common Issues

1. **Seeder Fails**
   - Check database connection
   - Ensure migrations are run
   - Verify model relationships

2. **SMS Sending Fails**
   - Check SMS driver configuration
   - Verify API credentials
   - Check rate limits

3. **Template Variables Not Replaced**
   - Verify JSON format in template variables
   - Check variable names match exactly
   - Ensure template is active

### Debug Information

Each test route provides detailed debug information:
- Execution times
- Error messages
- SMS provider responses
- Database query results

## Next Steps

After testing:

1. **Production Setup**: Configure real SMS provider credentials
2. **Custom Templates**: Create templates for your use case
3. **Campaign Scheduling**: Set up automated campaigns
4. **Monitoring**: Implement analytics tracking
5. **Webhooks**: Configure delivery status webhooks

## Support

For additional help:
- Review the main README.md for package documentation
- Check SMS provider documentation for API details
- Examine the SmsTestController for implementation examples
- Use the analytics endpoints to monitor performance