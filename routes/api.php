<?php

use Illuminate\Support\Facades\Route;
use MultiSms\Http\Controllers\SmsController;
use MultiSms\Http\Controllers\CampaignController;
use MultiSms\Http\Controllers\TemplateController;
use MultiSms\Http\Controllers\LogController;
use MultiSms\Http\Controllers\AutoresponderController;

/*
|--------------------------------------------------------------------------
| Multi-SMS API Routes
|--------------------------------------------------------------------------
|
| Here are the API routes for the Multi-SMS package. All routes are
| prefixed with 'multi-sms' and use the 'api' middleware group.
|
*/

Route::prefix('multi-sms')->middleware(['api'])->group(function () {
    
    // SMS Routes
    Route::prefix('sms')->group(function () {
        Route::post('send', [SmsController::class, 'send'])->name('multi-sms.sms.send');
        Route::post('send-bulk', [SmsController::class, 'sendBulk'])->name('multi-sms.sms.send-bulk');
        Route::get('status/{messageId}', [SmsController::class, 'status'])->name('multi-sms.sms.status');
        Route::get('drivers', [SmsController::class, 'drivers'])->name('multi-sms.sms.drivers');
        Route::post('test-driver', [SmsController::class, 'testDriver'])->name('multi-sms.sms.test-driver');
    });

    // Campaign Routes
    Route::prefix('campaigns')->group(function () {
        Route::get('/', [CampaignController::class, 'index'])->name('multi-sms.campaigns.index');
        Route::post('/', [CampaignController::class, 'store'])->name('multi-sms.campaigns.store');
        Route::get('{campaign}', [CampaignController::class, 'show'])->name('multi-sms.campaigns.show');
        Route::put('{campaign}', [CampaignController::class, 'update'])->name('multi-sms.campaigns.update');
        Route::delete('{campaign}', [CampaignController::class, 'destroy'])->name('multi-sms.campaigns.destroy');
        
        // Campaign Actions
        Route::post('{campaign}/start', [CampaignController::class, 'start'])->name('multi-sms.campaigns.start');
        Route::post('{campaign}/cancel', [CampaignController::class, 'cancel'])->name('multi-sms.campaigns.cancel');
        Route::get('{campaign}/stats', [CampaignController::class, 'stats'])->name('multi-sms.campaigns.stats');
    });

    // Template Routes
    Route::prefix('templates')->group(function () {
        Route::get('/', [TemplateController::class, 'index'])->name('multi-sms.templates.index');
        Route::post('/', [TemplateController::class, 'store'])->name('multi-sms.templates.store');
        Route::get('key/{key}', [TemplateController::class, 'getByKey'])->name('multi-sms.templates.by-key');
        Route::get('{template}', [TemplateController::class, 'show'])->name('multi-sms.templates.show');
        Route::put('{template}', [TemplateController::class, 'update'])->name('multi-sms.templates.update');
        Route::delete('{template}', [TemplateController::class, 'destroy'])->name('multi-sms.templates.destroy');
        
        // Template Actions
        Route::post('{template}/preview', [TemplateController::class, 'preview'])->name('multi-sms.templates.preview');
        Route::post('{template}/validate-variables', [TemplateController::class, 'validateVariables'])->name('multi-sms.templates.validate-variables');
        Route::post('{template}/toggle-status', [TemplateController::class, 'toggleStatus'])->name('multi-sms.templates.toggle-status');
    });

    // Log Routes
    Route::prefix('logs')->group(function () {
        Route::get('/', [LogController::class, 'index'])->name('multi-sms.logs.index');
        Route::get('{log}', [LogController::class, 'show'])->name('multi-sms.logs.show');
        Route::get('analytics/stats', [LogController::class, 'analytics'])->name('multi-sms.logs.analytics');
        Route::post('export', [LogController::class, 'export'])->name('multi-sms.logs.export');
        Route::delete('cleanup', [LogController::class, 'cleanup'])->name('multi-sms.logs.cleanup');
    });

    // Autoresponder Routes
    Route::prefix('autoresponders')->group(function () {
        Route::get('/', [AutoresponderController::class, 'index'])->name('multi-sms.autoresponders.index');
        Route::post('/', [AutoresponderController::class, 'store'])->name('multi-sms.autoresponders.store');
        Route::get('{autoresponder}', [AutoresponderController::class, 'show'])->name('multi-sms.autoresponders.show');
        Route::put('{autoresponder}', [AutoresponderController::class, 'update'])->name('multi-sms.autoresponders.update');
        Route::delete('{autoresponder}', [AutoresponderController::class, 'destroy'])->name('multi-sms.autoresponders.destroy');
        
        // Autoresponder Actions
        Route::post('{autoresponder}/toggle-status', [AutoresponderController::class, 'toggleStatus'])->name('multi-sms.autoresponders.toggle-status');
        Route::post('{autoresponder}/test', [AutoresponderController::class, 'test'])->name('multi-sms.autoresponders.test');
        Route::get('{autoresponder}/triggers', [AutoresponderController::class, 'triggers'])->name('multi-sms.autoresponders.triggers');
        Route::get('{autoresponder}/logs', [AutoresponderController::class, 'logs'])->name('multi-sms.autoresponders.logs');
        Route::get('{autoresponder}/stats', [AutoresponderController::class, 'stats'])->name('multi-sms.autoresponders.stats');
    });

    // Health Check Route
    Route::get('health', function () {
        return response()->json([
            'status' => 'ok',
            'package' => 'multi-sms',
            'version' => '1.0.0',
            'timestamp' => now()->toISOString()
        ]);
    })->name('multi-sms.health');

    // Configuration Route
    Route::get('config', function () {
        $config = config('multi-sms');
        
        // Remove sensitive information
        $safeConfig = [
            'default_driver' => $config['default_driver'] ?? null,
            'drivers' => array_keys($config['drivers'] ?? []),
            'fallback_drivers' => $config['fallback_drivers'] ?? [],
            'default_from' => $config['default_from'] ?? null,
            'logging' => [
                'enabled' => $config['logging']['enabled'] ?? false,
                'level' => $config['logging']['level'] ?? 'info'
            ],
            'rate_limiting' => $config['rate_limiting'] ?? [],
            'retry' => $config['retry'] ?? []
        ];
        
        return response()->json([
            'success' => true,
            'data' => $safeConfig
        ]);
    })->name('multi-sms.config');
});