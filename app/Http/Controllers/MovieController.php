<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\OmdbClient;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use RuntimeException;

class MovieController
{
    public function search(Request $request, OmdbClient $omdbClient): View
    {
        $movies = null;
        $error = null;

        if ($request->exists('query')) {
            $validatedParams = $request->validate([
                'query' => ['required', 'string', 'max:100'],
                'page' => ['nullable', 'integer', 'min:1', 'max:' . OmdbClient::MAX_PAGE],
            ]);

            $page = (int) ($validatedParams['page'] ?? 1);

            try {
                $response = $omdbClient->searchByTitle(
                    searchBy: $validatedParams['query'],
                    page: $page,
                );

                $moviesFoundTotal = (int) ($response['totalResults'] ?? 0);
                // OMDbAPI returns 10 results per page for a maximum of 100 pages
                $moviesAccessibleTotal = min($moviesFoundTotal, OmdbClient::MAX_ACCESSIBLE_RESULTS);

                $movies = new LengthAwarePaginator(
                    items: $response['Search'] ?? [],
                    total: $moviesAccessibleTotal,
                    perPage: OmdbClient::RESULTS_PER_PAGE,
                    currentPage: $page,
                    options: [
                        'path' => route('movies.search'),
                    ],
                );

                $movies->appends([
                    'query' => $validatedParams['query'],
                ]);
            } catch (RuntimeException $exception) {
                report($exception);

                $error = 'The service is temporarily unavailable. Please try again later.';
            }
        }

        return view('movies.search', [
            'movies' => $movies,
            'error' => $error,
            'moviesFoundTotal' => $moviesFoundTotal ?? 0,
        ]);
    }
}
