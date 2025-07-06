<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use MultiSms\Facades\Sms;

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

require __DIR__.'/auth.php';
