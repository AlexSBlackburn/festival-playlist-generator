<?php

namespace App\Console\Commands;

use App\Exceptions\AlbumsNotFoundException;
use App\Interfaces\FestivalService;
use App\Interfaces\StreamingService;
use App\Models\Playlist;
use Illuminate\Console\Command;

class UpdatePlaylist extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-playlist {year}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update a festival playlist, optionally create a playlist if it doesn\'t exist';

    /**
     * Execute the console command.
     */
    public function handle(StreamingService $streamingService, FestivalService $festivalService): void
    {
        try {
            $playlist = Playlist::where('year', $this->argument('year'))->first();

            if (! $playlist) {
                $this->info('No playlist found for the year '.$this->argument('year').'. Creating new playlist...');
                $this->newLine();

                $playlist = $streamingService->createPlaylist($this->argument('year'));

                $this->info('Playlist created for the year '.$playlist->year);
                $this->newLine();
            }

            $this->info('Playlist ID: '.$playlist->service_id);
            $this->newLine();
            $this->info('Adding bands to playlist...');
            $this->newLine();

            $failedBands = collect();

            $this->withProgressBar($festivalService->getBands(), function (string $band) use ($streamingService, $playlist, $failedBands): void {
                try {
                    $streamingService->updatePlaylist(playlist: $playlist, band: $band);
                } catch (AlbumsNotFoundException) {
                    $failedBands->add($band);
                }
            });
            $this->newLine();

            if ($failedBands->isNotEmpty()) {
                $this->newLine();
                $failedBands->each(function (string $band): void {
                    $this->info('No albums found for '.$band);
                });
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
