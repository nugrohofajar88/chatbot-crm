<?php

namespace App\Providers;

use App\Support\Contracts\WhatsappGateway;
use App\Support\FonnteService;
use App\Support\WablasService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Driver WhatsApp aktif (fonnte/wablas) via config WHATSAPP_DRIVER.
        $this->app->bind(WhatsappGateway::class, function ($app) {
            return $app->make(
                config('services.whatsapp.driver') === 'wablas'
                    ? WablasService::class
                    : FonnteService::class
            );
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
