<?php

namespace App\Services;

use App\Interfaces\FestivalService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class DesertfestService implements FestivalService
{
    public function getBands(): Collection
    {
        return Cache::remember(__CLASS__.__METHOD__, now()->addDay(), function () {
            return collect(Http::get(Config::get('services.desertfest.url'))->json())
                ->map(function (array $band) {
                    return html_entity_decode($band['title']['rendered']);
                });
        });
    }
}
