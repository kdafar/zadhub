<?php

namespace App\Providers;

use App\Services\WhatsAppApiServiceFactory;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(WhatsAppApiServiceFactory::class, function ($app) {
            return new WhatsAppApiServiceFactory;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
