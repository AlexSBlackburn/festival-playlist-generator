<?php

namespace App\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public $singletons = [
        \App\Interfaces\FestivalService::class => \App\Services\DesertfestService::class,
        \App\Interfaces\StreamingService::class => \App\Services\SpotifyService::class,
    ];

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
        Http::macro('spotify', function (string $token) {
            return Http::withHeaders([
                'Authentication' => 'Bearer '.$token,
            ])->baseUrl(Config::get('services.spotify.api_url'));
        });
    }
}
