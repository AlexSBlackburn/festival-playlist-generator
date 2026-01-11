<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Models\Playlist;

interface StreamingService
{
    public function createPlaylist(string $festivalName, int $year): Playlist;

    public function updatePlaylist(Playlist $playlist, string $band): void;
}
