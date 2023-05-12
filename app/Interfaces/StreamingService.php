<?php

namespace App\Interfaces;

use App\Models\Playlist;

interface StreamingService
{
    public function createPlaylist(int $year): Playlist;

    public function updatePlaylist(Playlist $playlist, string $band): void;
}
