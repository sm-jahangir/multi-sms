<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SmsTestController;
use App\Http\Controllers\SmsTemplateController;
use App\Http\Controllers\SmsCampaignController;
use App\Http\Controllers\SmsAutoresponderController;
use App\Http\Controllers\SmsAnalyticsController;
use App\Http\Controllers\SmsApiController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\ProfileController;
use MultiSms\Facades\Sms;
use Exception;

/*
|--------------------------------------------------------------------------
| Multi-SMS Package Web Routes
|--------------------------------------------------------------------------
|
| এই file এ multi-sms package এর সব web routes define করা আছে।
| এই routes গুলো আপনার main Laravel application এ integrate করতে পারেন।
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Simple SMS test route
Route::get('/test-sms', function () {
    try {
        // Test with fluent interface
        $result = Sms::to('+8801767275819')
            ->message('Hello! This is a test SMS from Multi-SMS package using fluent interface.')
            ->send();

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'message' => 'SMS sent successfully using fluent interface!',
                'message_id' => $result['message_id'] ?? null,
                'driver' => $result['driver'] ?? null,
                'response' => $result['response'] ?? null
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send SMS',
                'error' => $result['error'] ?? 'Unknown error'
            ]);
        }
    } catch (Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Exception occurred',
            'error' => $e->getMessage()
        ]);
    }
});

// Simple API test route
Route::get('/test-sms-simple', function () {
    try {
        // Test with simple API
        $result = Sms::send('+1234567890', 'Hello! This is a test SMS using simple API.');

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'message' => 'SMS sent successfully using simple API!',
                'message_id' => $result['message_id'] ?? null,
                'driver' => $result['driver'] ?? null,
                'response' => $result['response'] ?? null
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send SMS',
                'error' => $result['error'] ?? 'Unknown error'
            ]);
        }
    } catch (Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Exception occurred',
            'error' => $e->getMessage()
        ]);
    }
});

// Bulk SMS test route
Route::get('/test-sms-bulk', function () {
    try {
        $recipients = ['+1234567890', '+0987654321'];
        $results = Sms::to($recipients)
            ->message('Hello! This is a bulk SMS test.')
            ->send();

        return response()->json([
            'status' => 'success',
            'message' => 'Bulk SMS processing completed',
            'results' => $results
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Exception occurred',
            'error' => $e->getMessage()
        ]);
    }
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// SMS Dashboard এবং Main Routes
Route::prefix('sms')->name('sms.')->middleware(['web'])->group(function () {
    
    // Dashboard - SMS overview এবং statistics
    Route::get('/', [SmsTestController::class, 'dashboard'])->name('dashboard');
    
    // Original test routes (backward compatibility)
    Route::get('/test', [SmsTestController::class, 'index'])->name('index');
    Route::post('/send', [SmsTestController::class, 'send'])->name('send');
    Route::post('/send-bulk', [SmsTestController::class, 'sendBulk'])->name('send-bulk');
    Route::get('/logs', [SmsTestController::class, 'logs'])->name('logs');
    Route::post('/test-drivers', [SmsTestController::class, 'testDrivers'])->name('test-drivers');
    Route::post('/run-seeder', [SmsTestController::class, 'runSeeder'])->name('run-seeder');
    
    // Quick Actions - Dashboard থেকে direct actions
    Route::post('/test-send', [SmsTestController::class, 'sendTest'])->name('test.send');
    Route::get('/analytics-data', [SmsTestController::class, 'getAnalyticsData'])->name('analytics.data');
    
    /*
    |--------------------------------------------------------------------------
    | SMS Templates Management
    |--------------------------------------------------------------------------
    |
    | SMS templates create, edit, delete, duplicate এবং test করার জন্য routes
    |
    */
    Route::prefix('templates')->name('templates.')->group(function () {
        // Standard CRUD operations
        Route::get('/', [SmsTemplateController::class, 'index'])->name('index');
        Route::get('/create', [SmsTemplateController::class, 'create'])->name('create');
        Route::post('/', [SmsTemplateController::class, 'store'])->name('store');
        Route::get('/{template}', [SmsTemplateController::class, 'show'])->name('show');
        Route::get('/{template}/edit', [SmsTemplateController::class, 'edit'])->name('edit');
        Route::put('/{template}', [SmsTemplateController::class, 'update'])->name('update');
        Route::delete('/{template}', [SmsTemplateController::class, 'destroy'])->name('destroy');
        
        // Additional template actions
        Route::post('/{template}/duplicate', [SmsTemplateController::class, 'duplicate'])->name('duplicate');
        Route::post('/{template}/test', [SmsTemplateController::class, 'test'])->name('test');
        Route::post('/{template}/toggle-status', [SmsTemplateController::class, 'toggleStatus'])->name('toggle-status');
        
        // Bulk operations
        Route::post('/bulk-delete', [SmsTemplateController::class, 'bulkDelete'])->name('bulk-delete');
        Route::post('/bulk-export', [SmsTemplateController::class, 'bulkExport'])->name('bulk-export');
        Route::post('/import', [SmsTemplateController::class, 'import'])->name('import');
        
        // Template categories
        Route::get('/categories/list', [SmsTemplateController::class, 'getCategories'])->name('categories.list');
    });
    
    /*
    |--------------------------------------------------------------------------
    | SMS Campaigns Management
    |--------------------------------------------------------------------------
    |
    | SMS campaigns create, manage, schedule এবং monitor করার জন্য routes
    |
    */
    Route::prefix('campaigns')->name('campaigns.')->group(function () {
        // Standard CRUD operations
        Route::get('/', [SmsCampaignController::class, 'index'])->name('index');
        Route::get('/create', [SmsCampaignController::class, 'create'])->name('create');
        Route::post('/', [SmsCampaignController::class, 'store'])->name('store');
        Route::get('/{campaign}', [SmsCampaignController::class, 'show'])->name('show');
        Route::get('/{campaign}/edit', [SmsCampaignController::class, 'edit'])->name('edit');
        Route::put('/{campaign}', [SmsCampaignController::class, 'update'])->name('update');
        Route::delete('/{campaign}', [SmsCampaignController::class, 'destroy'])->name('destroy');
        
        // Campaign control actions
        Route::post('/{campaign}/start', [SmsCampaignController::class, 'start'])->name('start');
        Route::post('/{campaign}/pause', [SmsCampaignController::class, 'pause'])->name('pause');
        Route::post('/{campaign}/resume', [SmsCampaignController::class, 'resume'])->name('resume');
        Route::post('/{campaign}/stop', [SmsCampaignController::class, 'stop'])->name('stop');
        Route::post('/{campaign}/duplicate', [SmsCampaignController::class, 'duplicate'])->name('duplicate');
        Route::post('/{campaign}/test', [SmsCampaignController::class, 'test'])->name('test');
        
        // Campaign analytics
        Route::get('/{campaign}/analytics', [SmsCampaignController::class, 'analytics'])->name('analytics');
        Route::get('/{campaign}/logs', [SmsCampaignController::class, 'logs'])->name('logs');
        Route::get('/{campaign}/export', [SmsCampaignController::class, 'export'])->name('export');
        
        // Bulk operations
        Route::post('/bulk-start', [SmsCampaignController::class, 'bulkStart'])->name('bulk-start');
        Route::post('/bulk-pause', [SmsCampaignController::class, 'bulkPause'])->name('bulk-pause');
        Route::post('/bulk-delete', [SmsCampaignController::class, 'bulkDelete'])->name('bulk-delete');
        
        // Recipients management
        Route::post('/{campaign}/recipients/upload', [SmsCampaignController::class, 'uploadRecipients'])->name('recipients.upload');
        Route::get('/{campaign}/recipients/template', [SmsCampaignController::class, 'downloadRecipientsTemplate'])->name('recipients.template');
    });
    
    /*
    |--------------------------------------------------------------------------
    | SMS Autoresponders Management
    |--------------------------------------------------------------------------
    |
    | SMS autoresponders create, configure এবং manage করার জন্য routes
    |
    */
    Route::prefix('autoresponders')->name('autoresponders.')->group(function () {
        // Standard CRUD operations
        Route::get('/', [SmsAutoresponderController::class, 'index'])->name('index');
        Route::get('/create', [SmsAutoresponderController::class, 'create'])->name('create');
        Route::post('/', [SmsAutoresponderController::class, 'store'])->name('store');
        Route::get('/{autoresponder}', [SmsAutoresponderController::class, 'show'])->name('show');
        Route::get('/{autoresponder}/edit', [SmsAutoresponderController::class, 'edit'])->name('edit');
        Route::put('/{autoresponder}', [SmsAutoresponderController::class, 'update'])->name('update');
        Route::delete('/{autoresponder}', [SmsAutoresponderController::class, 'destroy'])->name('destroy');
        
        // Autoresponder control actions
        Route::post('/{autoresponder}/activate', [SmsAutoresponderController::class, 'activate'])->name('activate');
        Route::post('/{autoresponder}/deactivate', [SmsAutoresponderController::class, 'deactivate'])->name('deactivate');
        Route::post('/{autoresponder}/duplicate', [SmsAutoresponderController::class, 'duplicate'])->name('duplicate');
        Route::post('/{autoresponder}/test', [SmsAutoresponderController::class, 'test'])->name('test');
        
        // Autoresponder analytics
        Route::get('/{autoresponder}/analytics', [SmsAutoresponderController::class, 'analytics'])->name('analytics');
        Route::get('/{autoresponder}/logs', [SmsAutoresponderController::class, 'logs'])->name('logs');
        
        // Bulk operations
        Route::post('/bulk-activate', [SmsAutoresponderController::class, 'bulkActivate'])->name('bulk-activate');
        Route::post('/bulk-deactivate', [SmsAutoresponderController::class, 'bulkDeactivate'])->name('bulk-deactivate');
        Route::post('/bulk-delete', [SmsAutoresponderController::class, 'bulkDelete'])->name('bulk-delete');
        
        // Test keyword functionality
        Route::post('/test-keyword', [SmsAutoresponderController::class, 'testKeyword'])->name('test-keyword');
    });
    
    /*
    |--------------------------------------------------------------------------
    | SMS Analytics & Reports
    |--------------------------------------------------------------------------
    |
    | SMS analytics, reports এবং statistics এর জন্য routes
    |
    */
    Route::prefix('analytics')->name('analytics.')->group(function () {
        // Main analytics dashboard
        Route::get('/', [SmsAnalyticsController::class, 'index'])->name('index');
        
        // Data endpoints for charts and tables
        Route::get('/overview', [SmsAnalyticsController::class, 'getOverview'])->name('overview');
        Route::get('/trends', [SmsAnalyticsController::class, 'getTrends'])->name('trends');
        Route::get('/drivers', [SmsAnalyticsController::class, 'getDriverStats'])->name('drivers');
        Route::get('/errors', [SmsAnalyticsController::class, 'getErrorAnalysis'])->name('errors');
        Route::get('/costs', [SmsAnalyticsController::class, 'getCostAnalysis'])->name('costs');
        Route::get('/geographic', [SmsAnalyticsController::class, 'getGeographicData'])->name('geographic');
        
        // Campaign performance
        Route::get('/campaigns/performance', [SmsAnalyticsController::class, 'getCampaignPerformance'])->name('campaigns.performance');
        Route::get('/templates/usage', [SmsAnalyticsController::class, 'getTemplateUsage'])->name('templates.usage');
        Route::get('/autoresponders/stats', [SmsAnalyticsController::class, 'getAutoresponderStats'])->name('autoresponders.stats');
        
        // Recent activity
        Route::get('/activity', [SmsAnalyticsController::class, 'getRecentActivity'])->name('activity');
        
        // Export and reporting
        Route::post('/export', [SmsAnalyticsController::class, 'export'])->name('export');
        Route::post('/schedule-report', [SmsAnalyticsController::class, 'scheduleReport'])->name('schedule-report');
        Route::get('/reports', [SmsAnalyticsController::class, 'getScheduledReports'])->name('reports');
        Route::delete('/reports/{report}', [SmsAnalyticsController::class, 'deleteReport'])->name('reports.delete');
    });
    
    /*
    |--------------------------------------------------------------------------
    | SMS Settings & Configuration
    |--------------------------------------------------------------------------
    |
    | SMS settings, drivers configuration এবং system settings এর জন্য routes
    |
    */
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SmsTestController::class, 'settings'])->name('index');
        Route::post('/update', [SmsTestController::class, 'updateSettings'])->name('update');
        Route::post('/test-driver', [SmsTestController::class, 'testDriver'])->name('test-driver');
        Route::get('/drivers/status', [SmsTestController::class, 'getDriversStatus'])->name('drivers.status');
    });
});

/*
|--------------------------------------------------------------------------
| API Routes for AJAX Calls
|--------------------------------------------------------------------------
|
| Frontend থেকে AJAX calls এর জন্য API routes
| এই routes গুলো JSON response return করে
|
*/
Route::prefix('api/sms')->name('api.sms.')->middleware(['web'])->group(function () {
    
    // Quick test SMS send
    Route::post('/test', [SmsApiController::class, 'sendTest'])->name('test');
    
    // Templates API
    Route::prefix('templates')->name('templates.')->group(function () {
        Route::get('/', [SmsApiController::class, 'getTemplates'])->name('list');
        Route::get('/{template}', [SmsApiController::class, 'getTemplate'])->name('show');
        Route::post('/{template}/preview', [SmsApiController::class, 'previewTemplate'])->name('preview');
    });
    
    // Campaigns API
    Route::prefix('campaigns')->name('campaigns.')->group(function () {
        Route::get('/', [SmsApiController::class, 'getCampaigns'])->name('list');
        Route::get('/{campaign}', [SmsApiController::class, 'getCampaign'])->name('show');
        Route::get('/{campaign}/status', [SmsApiController::class, 'getCampaignStatus'])->name('status');
        Route::post('/{campaign}/preview', [SmsApiController::class, 'previewCampaign'])->name('preview');
    });
    
    // Autoresponders API
    Route::prefix('autoresponders')->name('autoresponders.')->group(function () {
        Route::get('/', [SmsApiController::class, 'getAutoresponders'])->name('list');
        Route::get('/{autoresponder}', [SmsApiController::class, 'getAutoresponder'])->name('show');
        Route::post('/test-trigger', [SmsApiController::class, 'testTrigger'])->name('test-trigger');
    });
    
    // Analytics API
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/dashboard', [SmsApiController::class, 'getDashboardData'])->name('dashboard');
        Route::get('/charts/{type}', [SmsApiController::class, 'getChartData'])->name('charts');
        Route::get('/stats/{period}', [SmsApiController::class, 'getStats'])->name('stats');
    });
    
    // System API
    Route::get('/drivers', [SmsApiController::class, 'getDrivers'])->name('drivers');
    Route::get('/status', [SmsApiController::class, 'getSystemStatus'])->name('status');
    Route::post('/validate-phone', [SmsApiController::class, 'validatePhone'])->name('validate-phone');
    Route::post('/detect-variables', [SmsApiController::class, 'detectVariables'])->name('detect-variables');
});

/*
|--------------------------------------------------------------------------
| Public Routes (No Authentication Required)
|--------------------------------------------------------------------------
|
| Public access routes যেমন webhook, unsubscribe links ইত্যাদি
|
*/
Route::prefix('sms/public')->name('sms.public.')->group(function () {
    
    // Webhook endpoints for SMS providers
    Route::post('/webhook/twilio', [SmsApiController::class, 'twilioWebhook'])->name('webhook.twilio');
    Route::post('/webhook/vonage', [SmsApiController::class, 'vonageWebhook'])->name('webhook.vonage');
    Route::post('/webhook/plivo', [SmsApiController::class, 'plivoWebhook'])->name('webhook.plivo');
    
    // Unsubscribe links
    Route::get('/unsubscribe/{token}', [SmsApiController::class, 'unsubscribe'])->name('unsubscribe');
    Route::post('/unsubscribe/{token}', [SmsApiController::class, 'processUnsubscribe'])->name('unsubscribe.process');
    
    // SMS reply handling (for autoresponders)
    Route::post('/reply', [SmsApiController::class, 'handleReply'])->name('reply');
    
    // Public status page
    Route::get('/status', [SmsApiController::class, 'publicStatus'])->name('status');
});

/*
|--------------------------------------------------------------------------
| Development & Testing Routes
|--------------------------------------------------------------------------
|
| Development এবং testing এর জন্য additional routes
| Production এ এই routes গুলো disable করা উচিত
|
*/
if (app()->environment(['local', 'staging'])) {
    Route::prefix('sms/dev')->name('sms.dev.')->middleware(['web'])->group(function () {
        
        // Database seeding
        Route::post('/seed/templates', [SmsTestController::class, 'seedTemplates'])->name('seed.templates');
        Route::post('/seed/campaigns', [SmsTestController::class, 'seedCampaigns'])->name('seed.campaigns');
        Route::post('/seed/autoresponders', [SmsTestController::class, 'seedAutoresponders'])->name('seed.autoresponders');
        Route::post('/seed/all', [SmsTestController::class, 'seedAll'])->name('seed.all');
        
        // Clear data
        Route::post('/clear/logs', [SmsTestController::class, 'clearLogs'])->name('clear.logs');
        Route::post('/clear/templates', [SmsTestController::class, 'clearTemplates'])->name('clear.templates');
        Route::post('/clear/campaigns', [SmsTestController::class, 'clearCampaigns'])->name('clear.campaigns');
        Route::post('/clear/all', [SmsTestController::class, 'clearAll'])->name('clear.all');
        
        // Test utilities
        Route::get('/test-drivers', [SmsTestController::class, 'testAllDrivers'])->name('test.drivers');
        Route::post('/simulate-webhook', [SmsTestController::class, 'simulateWebhook'])->name('simulate.webhook');
        Route::get('/debug-info', [SmsTestController::class, 'getDebugInfo'])->name('debug.info');
        
        // Performance testing
        Route::post('/stress-test', [SmsTestController::class, 'stressTest'])->name('stress.test');
        Route::get('/performance-metrics', [SmsTestController::class, 'getPerformanceMetrics'])->name('performance.metrics');
    });
}

// SMS Marketing Test Routes - Using SmsTestController for better organization
Route::prefix('sms-test')->group(function () {
    // Get available test routes
    Route::get('/routes', [App\Http\Controllers\SmsTestController::class, 'getTestRoutes']);

    // Seeder routes
    Route::get('/run-seeder', [App\Http\Controllers\SmsTestController::class, 'runSeeder']);

    // Template testing routes
    Route::get('/templates', [App\Http\Controllers\SmsTestController::class, 'getTemplates']);
    Route::get('/send-template/{templateId}', [App\Http\Controllers\SmsTestController::class, 'sendWithTemplate']);

    // Campaign testing routes
    Route::get('/campaigns', [App\Http\Controllers\SmsTestController::class, 'getCampaigns']);
    Route::get('/create-campaign', [App\Http\Controllers\SmsTestController::class, 'createTestCampaign']);

    // Analytics and logging routes
    Route::get('/logs/analytics', [App\Http\Controllers\SmsTestController::class, 'getAnalytics']);

    // Autoresponder testing routes
    Route::get('/autoresponders', [App\Http\Controllers\SmsTestController::class, 'getAutoresponders']);
    Route::get('/trigger-autoresponder/{keyword}', [App\Http\Controllers\SmsTestController::class, 'triggerAutoresponder']);

    // Service method testing routes
    Route::get('/service-methods', [App\Http\Controllers\SmsTestController::class, 'testServiceMethods']);
});

/*
|--------------------------------------------------------------------------
| Route Model Binding
|--------------------------------------------------------------------------
|
| Custom route model binding for SMS entities
| Note: এই bindings শুধুমাত্র তখনই কাজ করবে যখন corresponding models exist করবে
|
*/

// Uncomment these when you have the models
/*
Route::bind('template', function ($value) {
    return \MultiSms\Models\SmsTemplate::where('id', $value)
        ->orWhere('slug', $value)
        ->firstOrFail();
});

Route::bind('campaign', function ($value) {
    return \MultiSms\Models\SmsCampaign::where('id', $value)
        ->orWhere('slug', $value)
        ->firstOrFail();
});

Route::bind('autoresponder', function ($value) {
    return \MultiSms\Models\SmsAutoresponder::where('id', $value)
        ->orWhere('slug', $value)
        ->firstOrFail();
});

Route::bind('log', function ($value) {
    return \MultiSms\Models\SmsLog::findOrFail($value);
});
*/

require __DIR__.'/auth.php';
