<?php

use App\Http\Controllers\Api\LeagueController;
use Illuminate\Support\Facades\Route;

Route::prefix('/league')->group(function () {
    Route::get('/standings', [LeagueController::class, 'standings']);
    Route::get('/current-week', [LeagueController::class, 'currentWeek']);
    Route::get('/predictions', [LeagueController::class, 'predictions']);
    Route::get('/fixtures', [LeagueController::class, 'fixtures']);
    Route::put('/match/{id}', [LeagueController::class, 'editGame']);
    Route::post('/play-all', [LeagueController::class, 'playAll']);
    Route::post('/next-week', [LeagueController::class, 'nextWeek']);
});
