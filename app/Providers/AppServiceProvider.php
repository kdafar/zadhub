<?php

namespace App\Providers;

use App\Services\WhatsAppApiService;
use App\Services\WhatsAppApiServiceFake;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $useFake = config('services.whatsapp.fake', app()->environment('local'));

        $this->app->bind(WhatsAppApiService::class, function ($app) use ($useFake) {
            // resolve token/number id lazily at call site; constructor not strictly needed
            return $useFake ? new WhatsAppApiServiceFake : new WhatsAppApiService;
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
