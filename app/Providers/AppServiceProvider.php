<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\SpotifyService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public $singletons = [
        \App\Interfaces\FestivalService::class => \App\Services\DesertfestService::class,
        \App\Interfaces\StreamingService::class => SpotifyService::class,
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
         *
         * This is necessary because the Spotify API doesn't strictly match the artist name
         * when querying for albums. For example, "Sleep" might return albums by "Sleep" but
         * also "Sleep Token" or "Sleeping With Sirens".
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
