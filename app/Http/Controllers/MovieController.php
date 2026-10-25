<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\OmdbClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
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
        $moviesFoundTotal = 0;

        if ($request->exists('query')) {
            $validatedParams = $request->validate([
                'query' => ['required', 'string', 'max:100'],
                'page' => ['nullable', 'integer', 'min:1', 'max:' . OmdbClient::MAX_PAGE],
            ]);

            $query = trim($validatedParams['query']);
            $page = (int) ($validatedParams['page'] ?? 1);

            // A request without a page parameter is a new search, not pagination
            if ($request->missing('page')) {
                $this->rememberSearchQuery($request, $query);
            }

            try {
                $response = $omdbClient->searchByTitle(
                    searchBy: $query,
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
                    'query' => $query,
                ]);
            } catch (RuntimeException|ConnectionException|RequestException $exception) {
                report($exception);

                $error = 'The service is temporarily unavailable. Please try again later.';
            }
        }

        return view('movies.search', [
            'movies' => $movies,
            'error' => $error,
            'moviesFoundTotal' => $moviesFoundTotal,
            'moviesAccessibleLimit' => OmdbClient::MAX_ACCESSIBLE_RESULTS,
            'hasInaccessibleResults' => $moviesFoundTotal > OmdbClient::MAX_ACCESSIBLE_RESULTS,
            'recentSearches' => $request->session()->get('recent_movie_searches', []),
        ]);
    }

    public function show(string $imdbId, OmdbClient $omdbClient): View
    {
        $movie = null;
        $error = null;

        try {
            $movie = $omdbClient->findById($imdbId);
        } catch (RuntimeException|ConnectionException|RequestException $exception) {
            report($exception);

            $error = 'Movie details are temporarily unavailable. Please try again later.';
        }

        return view('movies.show', [
            'movie' => $movie,
            'error' => $error,
        ]);
    }

    private function rememberSearchQuery(Request $request, string $newQuery): void
    {
        $recentSearches = collect($request->session()->get('recent_movie_searches', []))
            ->reject(static fn (string $recentQuery): bool => mb_strtolower($recentQuery) === mb_strtolower($newQuery))
            ->prepend($newQuery)
            ->take(5)
            ->values()
            ->all();

        $request->session()->put('recent_movie_searches', $recentSearches);
    }
}
