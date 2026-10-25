<?php

declare(strict_types=1);

namespace App\Data;

final readonly class MovieDetails
{
    public function __construct(
        public string $title,
        public string $year,
        public string $imdbId,
        public ?string $type,
        public ?string $runtime,
        public ?string $poster,
        public ?string $plot,
        public ?string $imdbRating,
        public ?string $imdbVotes,
        public ?string $rottenTomatoesRating,
        public ?string $genre,
        public ?string $director,
        public ?string $writer,
        public ?string $actors,
        public ?string $language,
        public ?string $country,
        public ?string $awards,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromOmdb(array $data): self
    {
        return new self(
            title: self::stringValue($data, 'Title', 'Unknown title'),
            year: self::stringValue($data, 'Year', 'Unknown year'),
            imdbId: self::stringValue($data, 'imdbID'),
            type: self::optionalStringValue($data, 'Type'),
            runtime: self::optionalStringValue($data, 'Runtime'),
            poster: self::optionalStringValue($data, 'Poster'),
            plot: self::optionalStringValue($data, 'Plot'),
            imdbRating: self::optionalStringValue($data, 'imdbRating'),
            imdbVotes: self::optionalStringValue($data, 'imdbVotes'),
            rottenTomatoesRating: self::ratingValue($data, 'Rotten Tomatoes'),
            genre: self::optionalStringValue($data, 'Genre'),
            director: self::optionalStringValue($data, 'Director'),
            writer: self::optionalStringValue($data, 'Writer'),
            actors: self::optionalStringValue($data, 'Actors'),
            language: self::optionalStringValue($data, 'Language'),
            country: self::optionalStringValue($data, 'Country'),
            awards: self::optionalStringValue($data, 'Awards'),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function stringValue(array $data, string $key, string $default = ''): string
    {
        $value = $data[$key] ?? null;

        return is_string($value) ? $value : $default;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function optionalStringValue(array $data, string $key): ?string
    {
        $value = self::stringValue($data, $key);

        return $value !== '' && $value !== 'N/A' ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function ratingValue(array $data, string $source): ?string
    {
        $ratings = $data['Ratings'] ?? [];

        if (!is_array($ratings)) {
            return null;
        }

        foreach ($ratings as $rating) {
            if (
                is_array($rating)
                && ($rating['Source'] ?? null) === $source
                && is_string($rating['Value'] ?? null)
            ) {
                return self::optionalStringValue($rating, 'Value');
            }
        }

        return null;
    }
}
