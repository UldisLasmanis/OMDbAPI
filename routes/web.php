<?php

use App\Http\Controllers\MovieController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MovieController::class, 'search'])
    ->name('movies.search');

Route::get('/movies/{imdbId}', [MovieController::class, 'show'])
    ->where('imdbId', 'tt[0-9]+') //tt prefixes IMDb title IDs
    ->name('movies.show');
