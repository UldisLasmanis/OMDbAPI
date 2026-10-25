<?php

declare(strict_types=1);

namespace App\Data;

final readonly class MovieSearchResult
{
    public function __construct(
        public string $title,
        public string $year,
        public string $imdbId,
        public string $type,
        public ?string $poster,
    ) {}

    /**
     * @param array<string, mixed>  $data
     */
    public static function fromOmdb(array $data): self
    {
        $poster = $data['Poster'] ?? null;

        return new self(
            title: is_string($data['Title'] ?? null) ? $data['Title'] : 'Unknown title',
            year: is_string($data['Year'] ?? null) ? $data['Year'] : 'Unknown year',
            imdbId: is_string($data['imdbID'] ?? null) ? $data['imdbID'] : '',
            type: is_string($data['Type'] ?? null) ? $data['Type'] : 'unknown',
            poster: is_string($poster) && $poster !== '' && $poster !== 'N/A' ? $poster : null,
        );
    }
}
