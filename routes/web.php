<?php

use App\Http\Controllers\BandsExportController;use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/{streamingService}/authorize', function (string $streamingService) {
    return Socialite::driver($streamingService)->scopes(['playlist-modify-public'])->redirect();
});

Route::get('/{streamingService}/callback', function (string $streamingService) {
    $user = Socialite::driver($streamingService)->user();

    Cache::put($streamingService.'_refresh_token', $user->refreshToken);
    Cache::put($streamingService.'_access_token', $user->token, $user->expiresIn);

    echo 'Authorized user: '.$user->getName().'. Please re-run app:update-playlist command.';
});

Route::get('/bands-export/{filename}', [BandsExportController::class, 'index'])->name('export.bands.index');
