<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\TrackingService::class);
        $this->app->singleton(\App\Services\CampaignSchedulerService::class);
        $this->app->singleton(\App\Services\DnsVerificationService::class);
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
