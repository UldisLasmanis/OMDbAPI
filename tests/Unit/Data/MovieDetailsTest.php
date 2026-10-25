<?php

namespace Tests\Unit\Data;

use App\Data\MovieDetails;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MovieDetailsTest extends TestCase
{
    public function test_maps_movie_details(): void
    {
        $movie = MovieDetails::fromOmdb([
            'Title' => 'Breaking Bad',
            'Year' => '2008–2013',
            'imdbID' => 'tt0903747',
            'Type' => 'series',
            'Runtime' => '49 min',
            'Poster' => 'https://example.com/breaking-bad.jpg',
            'Plot' => 'A chemistry teacher turns to manufacturing methamphetamine.',
            'imdbRating' => '9.5',
            'imdbVotes' => '2,671,962',
            'Genre' => 'Crime, Drama, Thriller',
            'Director' => 'N/A',
            'Writer' => 'Vince Gilligan',
            'Actors' => 'Bryan Cranston, Aaron Paul, Anna Gunn',
            'Language' => 'English, Spanish',
            'Country' => 'United States',
            'Awards' => 'Won 16 Primetime Emmys.',
            'Ratings' => [
                ['Source' => 'Internet Movie Database', 'Value' => '9.5/10'],
                ['Source' => 'Rotten Tomatoes', 'Value' => '96%'],
            ],
        ]);

        $this->assertSame([
            'title' => 'Breaking Bad',
            'year' => '2008–2013',
            'imdbId' => 'tt0903747',
            'type' => 'series',
            'runtime' => '49 min',
            'poster' => 'https://example.com/breaking-bad.jpg',
            'plot' => 'A chemistry teacher turns to manufacturing methamphetamine.',
            'imdbRating' => '9.5',
            'imdbVotes' => '2,671,962',
            'rottenTomatoesRating' => '96%',
            'genre' => 'Crime, Drama, Thriller',
            'director' => null,
            'writer' => 'Vince Gilligan',
            'actors' => 'Bryan Cranston, Aaron Paul, Anna Gunn',
            'language' => 'English, Spanish',
            'country' => 'United States',
            'awards' => 'Won 16 Primetime Emmys.',
        ], get_object_vars($movie));
    }

    public function test_missing_fields_use_defaults(): void
    {
        $movie = MovieDetails::fromOmdb(['imdbID' => 'tt0903747']);

        $this->assertSame('Unknown title', $movie->title);
        $this->assertSame('Unknown year', $movie->year);
        $this->assertNull($movie->poster);
        $this->assertNull($movie->director);
        $this->assertNull($movie->imdbRating);
        $this->assertNull($movie->rottenTomatoesRating);
    }

    #[DataProvider('invalidValues')]
    public function test_invalid_optional_values_become_null(mixed $value): void
    {
        $movie = MovieDetails::fromOmdb([
            'imdbID' => 'tt0903747',
            'Poster' => $value,
            'Director' => $value,
            'imdbRating' => $value,
            'Ratings' => [['Source' => 'Rotten Tomatoes', 'Value' => $value]],
        ]);

        $this->assertNull($movie->poster);
        $this->assertNull($movie->director);
        $this->assertNull($movie->imdbRating);
        $this->assertNull($movie->rottenTomatoesRating);
    }

    public static function invalidValues(): array
    {
        return [
            'empty string' => [''],
            'N/A marker' => ['N/A'],
            'null' => [null],
            'unexpected number' => [123],
            'unexpected array' => [[]],
        ];
    }

    public function test_other_rating_sources_are_not_used_as_rotten_tomatoes(): void
    {
        $movie = MovieDetails::fromOmdb([
            'imdbID' => 'tt0903747',
            'Ratings' => [['Source' => 'Some other source', 'Value' => '9.5/10']],
        ]);

        $this->assertNull($movie->rottenTomatoesRating);
    }
}
