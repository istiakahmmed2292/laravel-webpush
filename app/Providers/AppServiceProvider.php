<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\WebPush\WebPushChannel;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Ensure the webpush notification channel is registered even if auto-discovery fails.
        Notification::resolved(function ($service) {
            $service->extend('webpush', function ($app) {
                return $app->make(WebPushChannel::class);
            });
        });
    }
}
