<?php

namespace App\Services;

use App\Interfaces\StreamingService;
use App\Models\Playlist;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
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
            ->post('/users/'.config('services.spotify.user_id').'/playlists', [
                'name' => 'Desertfest '.$year,
            ])->throw()->json();

        $playlist = new Playlist();
        $playlist->year = $year;
        $playlist->service_id = $spotifyPlaylist['id'];
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

            Http::spotify($this->getToken())->post('/playlists/'.$playlist->service_id.'/tracks', [
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

        // If the album is older than 3 years, get the most popular album instead
        if (Str::substr($album['release_date'], 0, 4) < now()->minusYears(3)->format('Y')) {
            $album = $albums->sortByDesc('popularity')->first();
        }

        return $album['uri'];
    }

    /**
     * Get the cached Spotify access token or refresh it
     */
    private function getToken(): string
    {
        if (Cache::has('spotify_access_token')) {
            return Cache::get('spotify_access_token');
        }

        if (! Cache::has('spotify_refresh_token')) {
            throw new \Exception('No refresh token found. Please visit http://localhost/spotify/authorize to request one.', 401);
        }

        $response = Http::asForm()
            ->withHeaders([
                'Authorization' => 'Basic '.str(config('services.spotify.client_id').':'.config('services.spotify.client_secret'))->toBase64(),
            ])
            ->post(config('services.spotify.token_url'), [
            'grant_type' => 'refresh_token',
            'refresh_token' => Cache::get('spotify_refresh_token'),
        ])->throw()->json();

        Cache::put('spotify_refresh_token', $response['refresh_token']);
        Cache::put('spotify_access_token', $response['access_token'], $response['expires_in']);

        return $response['access_token'];
    }

    private function setCurrentArtists(Playlist $playlist): void
    {
        $this->currentArtists = collect(Http::spotify($this->getToken())
            ->get('/playlists/'.$playlist->service_id.'/tracks', [
                'fields' => 'items(track(artists(name)))',
            ])->throw()->json())->pluck('track.artists.*.name')->flatten()->unique();
    }
}
