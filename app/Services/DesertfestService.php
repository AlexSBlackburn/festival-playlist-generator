<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\FestivalService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

final class DesertfestService implements FestivalService
{
    public function getFestivalName(): string
    {
        return 'Desertfest';
    }

    public function getBands(): Collection
    {
        return Cache::remember(__METHOD__, now()->addDay(), fn() => collect(Http::get(config('services.desertfest.url'))->json())
            ->map(fn(array $band): string => html_entity_decode((string) $band['title']['rendered'])));
    }
}
