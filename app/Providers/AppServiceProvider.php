<?php

namespace App\Providers;

use Illuminate\Support\Collection;
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
                'Authorization' => 'Bearer '.$token,
            ])->baseUrl(Config::get('services.spotify.api_url'));
        });

        Collection::macro('filterByArtist', function (string $band): Collection {
            $band = str($band)->title();
            $bandNames = collect([$band]);
            $bandNames->add($band->replace('&', 'And'));
            $bandNames->add($band->replace('And', '&'));

            /** @var Collection $this */
            return $this->filter(fn (array $album) => $bandNames->contains(str($album['artists'][0]['name'])->title()));
        });
    }
}
