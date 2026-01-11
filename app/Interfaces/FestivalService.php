<?php

declare(strict_types=1);

namespace App\Interfaces;

use Illuminate\Support\Collection;

interface FestivalService
{
    public function getFestivalName(): string;

    public function getBands(): Collection;
}
