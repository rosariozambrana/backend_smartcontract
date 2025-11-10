<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Blockchain services as singletons to prevent memory leaks
        $this->app->singleton(\App\Services\Blockchain\BlockchainService::class, function ($app) {
            return new \App\Services\Blockchain\BlockchainService();
        });

        $this->app->singleton(\App\Services\Blockchain\RentalContractService::class, function ($app) {
            return new \App\Services\Blockchain\RentalContractService();
        });

        $this->app->singleton(\App\Services\Blockchain\WalletService::class, function ($app) {
            return new \App\Services\Blockchain\WalletService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
    }
}
