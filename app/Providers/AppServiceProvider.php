<?php

namespace App\Providers;

use App\Services\Olx\OlxStateFetcher;
use App\Services\Olx\PriceFetcherInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PriceFetcherInterface::class, OlxStateFetcher::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
