<?php

namespace Tests\Unit\Data;

use App\Data\MovieSearchResponse;
use App\Data\MovieSearchResult;
use PHPUnit\Framework\TestCase;

class MovieSearchResponseTest extends TestCase
{
    public function test_maps_results_and_converts_total_to_integer(): void
    {
        $response = MovieSearchResponse::fromOmdb([
            'Search' => [
                ['Title' => 'The Matrix', 'imdbID' => 'tt0133093'],
                ['Title' => 'The Matrix Reloaded', 'imdbID' => 'tt0234215'],
            ],
            'totalResults' => '25',
        ]);

        $this->assertSame(25, $response->totalResults);
        $this->assertInstanceOf(MovieSearchResult::class, $response->movies[0]);
        $this->assertSame('tt0133093', $response->movies[0]->imdbId);
        $this->assertSame('tt0234215', $response->movies[1]->imdbId);
    }

    public function test_maps_empty_results(): void
    {
        $response = MovieSearchResponse::fromOmdb([
            'Search' => [],
            'totalResults' => '0',
        ]);

        $this->assertSame([], $response->movies);
        $this->assertSame(0, $response->totalResults);
    }

    public function test_missing_fields_default_to_empty_results(): void
    {
        $response = MovieSearchResponse::fromOmdb([]);

        $this->assertSame([], $response->movies);
        $this->assertSame(0, $response->totalResults);
    }
}
