<?php

namespace MultiSms;

use Illuminate\Support\ServiceProvider;
use MultiSms\Services\SmsService;
use MultiSms\Console\Commands\SendSmsCommand;
use MultiSms\Console\Commands\RunCampaignsCommand;

class MultiSmsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/multi-sms.php', 'multi-sms'
        );

        $this->app->singleton(SmsService::class, function ($app) {
            return new SmsService($app['config']['multi-sms']);
        });

        $this->app->alias(SmsService::class, 'sms');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/multi-sms.php' => config_path('multi-sms.php'),
            ], 'multi-sms-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'multi-sms-migrations');

            $this->commands([
                SendSmsCommand::class,
                RunCampaignsCommand::class,
            ]);
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [SmsService::class, 'sms'];
    }
}