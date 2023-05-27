<?php

namespace App\Services;

use App\Interfaces\StreamingService;
use App\Models\Playlist;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SpotifyService implements StreamingService
{
    public function __construct(
        private Collection $currentArtists
    )
    {
    }

    public function createPlaylist(int $year): Playlist
    {
        $spotifyPlaylist = Http::spotify($this->getToken())
            ->post('/users/'.Config::get('services.spotify.user_id').'/playlists', [
                'name' => 'Desertfest '.$year,
            ])->throw()->json();

        $playlist = new Playlist();
        $playlist->year = $year;
        $playlist->spotify_id = $spotifyPlaylist['id'];
        $playlist->save();

        return $playlist;
    }

    public function updatePlaylist(Playlist $playlist, string $band): void
    {
        if (!$this->currentArtists) {
            $this->setCurrentArtists($playlist);
        }

        if ($this->currentArtists->doesntContain($band)) {
            $album = $this->getMostRecentOrPopularAlbum($band);

            Http::spotify($this->getToken())->post('/playlists/'.$playlist->spotify_id.'/tracks', [
                'uris' => $album,
            ])->throw();
        }
    }

    private function getMostRecentOrPopularAlbum(string $band): string
    {
        $albums = collect(Http::spotify($this->getToken())->get('/search', [
            'q' => 'artist:'.$band,
            'type' => 'album',
        ])->throw()->json()['albums']['items'])
            ->filter(fn($album) => $album['album_type'] === 'album')
            ->sortByDesc('release_date');

        $album = $albums->first();

        // If the album is older than a year, get the most popular album instead
        if (Str::substr($album['release_date'], 0, 4) < now()->minusYears(1)->format('Y')) {
            $album = $albums->sortByDesc('popularity')->first();
        }

        return $album['uri'];
    }

    private function getToken(): string
    {
        if (Cache::has('spotify_token')) {
            return Cache::get('spotify_token');
        }

        $response = Http::asForm()->post(Config::get('services.spotify.token_url'), [
            'grant_type' => 'client_credentials',
            'client_id' => Config::get('services.spotify.client_id'),
            'client_secret' => Config::get('services.spotify.client_secret'),
        ])->throw()->json();

        Cache::put('spotify_token', $response['access_token'], $response['expires_in']);

        return $response['access_token'];
    }

    private function setCurrentArtists(Playlist $playlist): void
    {
        $this->currentArtists = collect(Http::spotify($this->getToken())
            ->get('/playlists/'.$playlist->spotify_id.'/tracks', [
                'fields' => 'items(track(artists(name)))',
            ])->throw()->json())->pluck('track.artists.*.name')->flatten()->unique();
    }
}
