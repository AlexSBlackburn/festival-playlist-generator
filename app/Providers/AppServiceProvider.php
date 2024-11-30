<?php

namespace App\Providers;

use App\Services\SpotifyService;
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
        Http::macro('spotify', fn () => Http::withHeaders([
            'Authorization' => 'Bearer '.SpotifyService::getToken(),
        ])->baseUrl(Config::get('services.spotify.api_url')));

        /*
         * Filter albums by artist name, including common variations
         */
        Collection::macro('filterByArtist', function (string $band): Collection {
            $band = str($band)->lower();
            $bandNames = collect([$band]);
            $bandNames->add($band->replace('&', 'and'));
            $bandNames->add($band->replace('and', '&'));

            /** @var Collection $this */
            return $this->filter(fn (array $album) => $bandNames->contains(str($album['artists'][0]['name'])->lower()));
        });
    }
}
