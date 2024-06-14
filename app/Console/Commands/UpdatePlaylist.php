<?php

namespace App\Console\Commands;

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

            if (!$playlist) {
                $this->info('No playlist found for the year '.$this->argument('year').'. Creating new playlist...');

                $playlist = $streamingService->createPlaylist($this->argument('year'));

                $this->info('Playlist created for the year '.$playlist->year);
            }

            $this->info('Playlist ID: ' . $playlist->service_id);

            $this->withProgressBar($festivalService->getBands(), function (string $band) use ($streamingService, $playlist): void {
                $streamingService->updatePlaylist(playlist: $playlist, band: $band);
            });
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
