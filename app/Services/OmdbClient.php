<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\RequestException;
use RuntimeException;

class OmdbClient
{
    public const int RESULTS_PER_PAGE = 10;
    public const int MAX_PAGE = 100;
    public const int MAX_ACCESSIBLE_RESULTS = 1_000;
    private const string UNKNOWN_ERROR = 'OMDb returned an unknown error';

    public function __construct(
        private readonly Factory $http,
        private readonly string $apiKey,
        private readonly string $baseUrl,
    ) {}

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function searchByTitle(string $searchBy, int $page): array
    {
        $data = $this->http
            ->baseUrl($this->baseUrl)
            ->retry(2, 200)
            ->get('/', [
                'apikey' => $this->apiKey,
                's' => $searchBy,
                'page' => $page,
            ])
            ->throw()
            ->json();

        if (($data['Response'] ?? 'False') === 'False') {
            if (($data['Error'] ?? null) === 'Movie not found!') {
                return [
                    'Search' => [],
                    'totalResults' => '0',
                ];
            }

            throw new RuntimeException(
                $data['Error'] ?? self::UNKNOWN_ERROR,
            );
        }

        return $data;
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function findById(string $imdbId): array
    {
        $data = $this->http
            ->baseUrl($this->baseUrl)
            ->retry(2, 200)
            ->get('/', [
                'apikey' => $this->apiKey,
                'i' => $imdbId,
                'plot' => 'full',
            ])
            ->throw()
            ->json();

        if (($data['Response'] ?? 'False') === 'False') {
            throw new RuntimeException(
                $data['Error'] ?? self::UNKNOWN_ERROR,
            );
        }

        return $data;
    }
}
