<?php

namespace Tests\Unit\Data;

use App\Data\MovieSearchResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MovieSearchResultTest extends TestCase
{
    public function test_maps_search_result_fields(): void
    {
        $movie = MovieSearchResult::fromOmdb([
            'Title' => 'Breaking Bad',
            'Year' => '2008–2013',
            'imdbID' => 'tt0903747',
            'Type' => 'series',
            'Poster' => 'https://example.com/breaking-bad.jpg',
        ]);

        $this->assertSame('Breaking Bad', $movie->title);
        $this->assertSame('2008–2013', $movie->year);
        $this->assertSame('tt0903747', $movie->imdbId);
        $this->assertSame('series', $movie->type);
        $this->assertSame('https://example.com/breaking-bad.jpg', $movie->poster);
    }

    public function test_missing_display_fields_use_defaults(): void
    {
        $movie = MovieSearchResult::fromOmdb(['imdbID' => 'tt0903747']);

        $this->assertSame('Unknown title', $movie->title);
        $this->assertSame('Unknown year', $movie->year);
        $this->assertSame('unknown', $movie->type);
        $this->assertNull($movie->poster);
    }

    #[DataProvider('invalidPosterValues')]
    public function test_invalid_poster_values_become_null(mixed $poster): void
    {
        $movie = MovieSearchResult::fromOmdb([
            'imdbID' => 'tt0903747',
            'Poster' => $poster,
        ]);

        $this->assertNull($movie->poster);
    }

    public static function invalidPosterValues(): array
    {
        return [
            'empty string' => [''],
            'N/A marker' => ['N/A'],
            'null' => [null],
            'unexpected number' => [123],
        ];
    }
}
