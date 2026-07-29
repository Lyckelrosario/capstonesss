<?php

namespace App\Providers;

use App\Services\ActivityLogger;
use App\Services\NotificationService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NotificationService::class, fn () => new NotificationService());
        $this->app->singleton(ActivityLogger::class, fn () => new ActivityLogger());
    }

    public function boot(): void
    {
        //
    }
}
