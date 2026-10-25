<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

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
        $cacheKey = sprintf(
            'omdb:search:%s:page:%d',
            mb_strtolower(trim($searchBy)),
            $page,
        );

        $cachedResponse = Cache::get($cacheKey);
        if (is_array($cachedResponse)) {
            return $cachedResponse;
        }

        $data = $this->createRequest()
            ->get('/', [
                'apikey' => $this->apiKey,
                's' => $searchBy,
                'page' => $page,
            ])
            ->throw()
            ->json();

        if (($data['Response'] ?? 'False') === 'False') {
            if (($data['Error'] ?? null) === 'Movie not found!') {
                $data = [
                    'Search' => [],
                    'totalResults' => '0',
                ];
            } else {
                throw new RuntimeException(
                    $data['Error'] ?? self::UNKNOWN_ERROR,
                );
            }
        }

        Cache::put($cacheKey, $data, now()->addHour());

        return $data;
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function findById(string $imdbId): array
    {
        $cacheKey = sprintf(
            'omdb:movie:%s',
            mb_strtolower($imdbId),
        );

        $cachedResponse = Cache::get($cacheKey);
        if (is_array($cachedResponse)) {
            return $cachedResponse;
        }

        $data = $this->createRequest()
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

        Cache::put($cacheKey, $data, now()->addHour());

        return $data;
    }

    private function createRequest(): PendingRequest
    {
        return $this->http
            ->createPendingRequest()
            ->baseUrl($this->baseUrl)
            ->connectTimeout(2)
            ->timeout(5)
            ->retry(
                2,
                200,
                static function (Throwable $exception): bool {
                    if ($exception instanceof ConnectionException) {
                        return true;
                    }

                    if (! $exception instanceof RequestException) {
                        return false;
                    }

                    return in_array(
                        $exception->response->status(),
                        [429, 500, 502, 503, 504],
                        true,
                    );
                },
            );
    }
}
