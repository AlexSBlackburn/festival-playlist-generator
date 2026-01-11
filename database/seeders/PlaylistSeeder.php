<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Playlist;
use Illuminate\Database\Seeder;

final class PlaylistSeeder extends Seeder
{
    public function run(): void
    {
        Playlist::factory()->count(2)->create();
    }
}
