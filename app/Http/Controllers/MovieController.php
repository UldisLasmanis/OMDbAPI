<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\OmdbClient;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class MovieController
{
    public function search(Request $request, OmdbClient $omdbClient): View
    {
        $movies = [];
        $error = null;

        if ($request->filled('query')) {
            $validatedParams = $request->validate([
                'query' => ['required', 'string', 'max:100'],
            ]);

            try {
                $response = $omdbClient->searchByTitle(
                    $validatedParams['query']
                );

                $movies = $response['Search'] ?? [];
            } catch (RuntimeException $exception) {
                report($exception);

                $error = 'The service is temporarily unavailable. Please try again later.';
            }
        } else if ($request->exists('query')) {
            $error = 'Please enter a search query.';
        }

        return view('movies.search', [
            'movies' => $movies,
            'error' => $error,
        ]);
    }
}
