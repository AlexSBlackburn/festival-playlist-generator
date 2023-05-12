<?php

namespace App\Services;

use App\Interfaces\StreamingService;
use App\Models\Playlist;

class SpotifyService implements StreamingService
{
    public function createPlaylist(int $year): Playlist
    {
        // TODO: Implement Spotify API call to create playlist
        $spotifyPlaylist = [
            'year' => $year,
            'spotify_id' => '1234567890123456789012',
        ];

        $playlist = new Playlist();
        $playlist->year = $spotifyPlaylist['year'];
        $playlist->spotify_id = $spotifyPlaylist['spotify_id'];
        $playlist->save();

        return $playlist;
    }

    public function updatePlaylist(Playlist $playlist, array $band): void
    {
        // TODO:
        // 6. For each band, check if it is already in playlist
        // 7. If not, get latest or most popular album from Spotify
        // 8. Add album to playlist
    }
}
