<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\FestivalService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

final class DesertfestService implements FestivalService
{
    public function getBands(): Collection
    {
        return Cache::remember(__METHOD__, now()->addDay(), function () {
            return collect(Http::get(config('services.desertfest.url'))->json())
                ->map(function (array $band) {
                    return html_entity_decode($band['title']['rendered']);
                });
        });
    }
}
