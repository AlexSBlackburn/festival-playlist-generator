<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Exceptions\AlbumsNotFoundException;
use App\Interfaces\FestivalService;
use App\Interfaces\StreamingService;
use App\Models\Playlist;
use Exception;
use Illuminate\Console\Command;

final class UpdatePlaylist extends Command
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
    protected $description = "Update a festival playlist, optionally create a playlist if it doesn't exist";

    /**
     * Execute the console command.
     */
    public function handle(StreamingService $streamingService, FestivalService $festivalService): void
    {
        try {
            $playlist = Playlist::where('year', $this->argument('year'))->first();

            if (! $playlist) {
                $this->line('No playlist found for the year '.$this->argument('year').'. Creating new playlist...');
                $this->newLine();

                $playlist = $streamingService->createPlaylist($this->argument('year'));

                $this->info('Playlist created for the year '.$playlist->year);
                $this->newLine();
            }

            $this->line('Playlist ID: '.$playlist->service_id);
            $this->newLine();
            $this->line('Adding bands to playlist...');
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
                    $this->line('No albums found for '.$band);
                });
            }

            $this->newLine();
            $filename = 'desertfest-'.$this->argument('year').'.csv';
            $this->info('Visit '.route('export.bands.index', $filename).' to download the CSV.');
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
    }
}
