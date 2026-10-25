<?php

declare(strict_types=1);

namespace App\Data;

final readonly class MovieSearchResponse
{
    /**
     * @param  list<MovieSearchResult>  $movies
     */
    public function __construct(
        public array $movies,
        public int $totalResults,
    ) {}

    /**
     * @param  array{Search?: mixed, totalResults?: mixed}  $data
     */
    public static function fromOmdb(array $data): self
    {
        $movies = [];
        $searchResults = $data['Search'] ?? [];

        if (is_array($searchResults)) {
            foreach ($searchResults as $searchResult) {
                if (is_array($searchResult)) {
                    $movies[] = MovieSearchResult::fromOmdb($searchResult);
                }
            }
        }

        $totalResults = $data['totalResults'] ?? 0;

        return new self(
            movies: $movies,
            totalResults: is_numeric($totalResults) ? (int) $totalResults : 0,
        );
    }
}
