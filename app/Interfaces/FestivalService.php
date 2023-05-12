<?php

namespace App\Interfaces;

use Illuminate\Support\Collection;

interface FestivalService
{
    public function getBands(): Collection;
}
