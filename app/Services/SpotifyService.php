<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AlbumsNotFoundException;
use App\Interfaces\StreamingService;
use App\Models\Playlist;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class SpotifyService implements StreamingService
{
    /**
     * Get the cached Spotify access token or refresh it
     */
    public static function getToken(): string
    {
        if (Cache::has('spotify_access_token')) {
            return Cache::get('spotify_access_token');
        }

        if (! Cache::has('spotify_refresh_token')) {
            throw new Exception(sprintf('No refresh token found. Please visit %s to request one.', config('app.url').'/spotify/authorize'), 401);
        }

        $response = Http::asForm()
            ->withHeaders([
                'Authorization' => 'Basic '.base64_encode(config('services.spotify.client_id').':'.config('services.spotify.client_secret')),
            ])
            ->post(config('services.spotify.token_url'), [
                'grant_type' => 'refresh_token',
                'refresh_token' => Cache::get('spotify_refresh_token'),
            ])->throw()->json();

        Cache::put('spotify_access_token', $response['access_token'], $response['expires_in']);

        return $response['access_token'];
    }

    public function createPlaylist(int $year): Playlist
    {
        $spotifyPlaylist = Http::spotify()
            ->post('/users/'.config('services.spotify.user_id').'/playlists', [
                'name' => 'Desertfest '.$year,
            ])->throw()->json();

        $playlist = new Playlist;
        $playlist->year = $year;
        $playlist->service_id = $spotifyPlaylist['id'];
        $playlist->save();

        return $playlist;
    }

    public function updatePlaylist(Playlist $playlist, string $band): void
    {
        if ($playlist->bands->doesntContain($band)) {
            $playlist->bands = $playlist->bands->push($band);
            $playlist->save();

            $album = $this->getMostRecentOrPopularAlbum($band);

            Http::spotify()->post('/playlists/'.$playlist->service_id.'/tracks', [
                'uris' => $this->getAlbumTracks($album['id'])->pluck('uri'),
            ])->throw();
        }
    }

    private function getMostRecentOrPopularAlbum(string $band): array
    {
        $albums = $this->getAlbumsByArtist($band);

        $fullLengths = $albums->filter(fn (array $album) => $album['album_type'] === 'album');
        $epsAndSingles = $albums->filter(fn (array $album) => $album['album_type'] === 'single');

        // Get albums, then EPs, then singles
        if ($fullLengths->isNotEmpty()) {
            $albums = $fullLengths;
        } else {
            $eps = $epsAndSingles->filter(fn (array $item) => $item['total_tracks'] > 1);

            if ($eps->isNotEmpty()) {
                $albums = $eps;
            } else {
                $albums = $epsAndSingles;
            }
        }

        $album = $albums->first();

        // If the album is older than 3 years, get the most popular album instead
        if (Str::substr($album['release_date'], 0, 4) < now()->subYears(3)->format('Y')) {
            $album = $albums->sortByDesc('popularity')->first();
        }

        return $album;
    }

    private function getAlbumsByArtist(string $band, int $offset = 0): Collection
    {
        $response = Http::spotify()
            ->get('/search', [
                'q' => 'artist:"'.$band.'"',
                'type' => 'album',
                'offset' => $offset,
                'limit' => 50,
            ])
            ->throw();

        $albums = collect($response['albums']['items'])
            ->reject(fn (array $album) => $album['album_type'] === 'compilation')
            ->reject(fn (array $album) => str($album['name'])->lower()->contains(['live', 'soundtrack']))
            ->reject(fn (array $album) => count($album['artists']) > 1) // Reject split EPs
            ->filterByArtist($band) // Artist query is not an exact match, so filter albums by other artists
            ->sortByDesc('release_date');

        if ($albums->isEmpty()) {
            $offset += 50;
            if ($response['albums']['total'] > $offset) {
                $albums = $this->getAlbumsByArtist($band, $offset);
            }
        }

        if ($albums->isEmpty()) {
            throw new AlbumsNotFoundException('No albums found for '.$band);
        }

        return $albums;
    }

    private function getAlbumTracks(string $albumId): Collection
    {
        return Http::spotify()
            ->get('/albums/'.$albumId.'/tracks')
            ->throw()
            ->collect(['items']);
    }
}
