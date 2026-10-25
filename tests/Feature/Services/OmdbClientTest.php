<?php

namespace Tests\Feature\Services;

use App\Data\MovieDetails;
use App\Data\MovieSearchResponse;
use App\Services\OmdbClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class OmdbClientTest extends TestCase
{
    private const string OMDB_BASE_URL = 'https://www.omdbapi.com';
    private const string OMDB_URL_PATTERN = self::OMDB_BASE_URL.'/*';
    private const string INVALID_API_KEY_MESSAGE = 'Invalid API key!';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.omdb.base_url' => self::OMDB_BASE_URL,
        ]);
    }

    public function test_search_by_title_sends_expected_parameters_and_returns_results(): void
    {
        $omdbResponse = [
            'Search' => [
                [
                    'Title' => 'Breaking Bad',
                    'Year' => '2008–2013',
                    'imdbID' => 'tt0903747',
                    'Type' => 'series',
                    'Poster' => 'https://example.com/breaking-bad.jpg',
                ],
            ],
            'totalResults' => '1',
            'Response' => 'True',
        ];

        Http::preventStrayRequests();
        Http::fake([
            self::OMDB_URL_PATTERN => Http::response($omdbResponse),
        ]);

        $result = app(OmdbClient::class)->searchByTitle(
            searchBy: 'Breaking Bad',
            page: 2,
        );

        $this->assertInstanceOf(MovieSearchResponse::class, $result);
        $this->assertSame(1, $result->totalResults);
        $this->assertCount(1, $result->movies);
        $this->assertSame('Breaking Bad', $result->movies[0]->title);
        $this->assertSame('tt0903747', $result->movies[0]->imdbId);

        Http::assertSent(
            static fn (Request $request): bool => $request->method() === 'GET'
                && $request->data()['apikey'] === 'test-api-key'
                && $request->data()['s'] === 'Breaking Bad'
                && $request->data()['page'] === 2,
        );

        Http::assertSentCount(1);
    }

    #[DataProvider('searchCacheScenarios')]
    public function test_search_by_title_caches_responses_by_query_and_page(
        array $searches,
        array $omdbResponse,
        int $expectedOmdbRequests,
    ): void {
        Http::preventStrayRequests();
        Http::fake([
            self::OMDB_URL_PATTERN => Http::response($omdbResponse),
        ]);

        $omdbClient = app(OmdbClient::class);

        foreach ($searches as $search) {
            $omdbClient->searchByTitle(
                searchBy: $search['query'],
                page: $search['page'],
            );
        }

        Http::assertSentCount($expectedOmdbRequests);
    }

    public static function searchCacheScenarios(): array
    {
        $successfulResponse = [
            'Search' => [[
                'Title' => 'The Matrix',
                'Year' => '1999',
                'imdbID' => 'tt0133093',
                'Type' => 'movie',
                'Poster' => 'N/A',
            ]],
            'totalResults' => '1',
            'Response' => 'True',
        ];

        $emptyResponse = [
            'Response' => 'False',
            'Error' => 'Movie not found!',
        ];

        return [
            'same query and page use cached response' => [
                [
                    ['query' => 'Matrix', 'page' => 1],
                    ['query' => 'Matrix', 'page' => 1],
                ],
                $successfulResponse,
                1,
            ],
            'pages have separate cache entries' => [
                [
                    ['query' => 'Matrix', 'page' => 1],
                    ['query' => 'Matrix', 'page' => 2],
                    ['query' => 'Matrix', 'page' => 1],
                ],
                $successfulResponse,
                2,
            ],
            'queries have separate cache entries' => [
                [
                    ['query' => 'Matrix', 'page' => 1],
                    ['query' => 'Shrek', 'page' => 1],
                    ['query' => 'Matrix', 'page' => 1],
                ],
                $successfulResponse,
                2,
            ],
            'empty results are cached' => [
                [
                    ['query' => 'Missing movie', 'page' => 1],
                    ['query' => 'Missing movie', 'page' => 1],
                ],
                $emptyResponse,
                1,
            ],
            'query case does not create another cache entry' => [
                [
                    ['query' => 'Matrix', 'page' => 1],
                    ['query' => 'MATRIX', 'page' => 1],
                    ['query' => 'matrix', 'page' => 1],
                ],
                $successfulResponse,
                1,
            ],
        ];
    }

    public function test_search_by_title_failed_responses_are_not_cached(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            self::OMDB_URL_PATTERN => Http::response([
                'Response' => 'False',
                'Error' => self::INVALID_API_KEY_MESSAGE,
            ]),
        ]);

        $omdbClient = app(OmdbClient::class);

        $this->assertThrows(
            static fn () => $omdbClient->searchByTitle('Matrix', 1),
            RuntimeException::class,
            self::INVALID_API_KEY_MESSAGE,
        );
        $this->assertThrows(
            static fn () => $omdbClient->searchByTitle('Matrix', 1),
            RuntimeException::class,
            self::INVALID_API_KEY_MESSAGE,
        );

        Http::assertSentCount(2);
    }

    public function test_find_by_id_sends_expected_parameters_and_returns_movie_details(): void
    {
        $movieDetails = [
            'Title' => 'Breaking Bad',
            'Year' => '2008–2013',
            'Runtime' => '49 min',
            'Plot' => 'A chemistry teacher turns to manufacturing methamphetamine.',
            'imdbID' => 'tt0903747',
            'Type' => 'series',
            'Response' => 'True',
        ];

        Http::preventStrayRequests();
        Http::fake([
            self::OMDB_URL_PATTERN => Http::response($movieDetails),
        ]);

        $result = app(OmdbClient::class)->findById('tt0903747');

        $this->assertInstanceOf(MovieDetails::class, $result);
        $this->assertSame('Breaking Bad', $result->title);
        $this->assertSame('49 min', $result->runtime);
        $this->assertSame('tt0903747', $result->imdbId);
        $this->assertSame('series', $result->type);

        Http::assertSent(
            static fn (Request $request): bool => $request->method() === 'GET'
                && $request->data()['apikey'] === 'test-api-key'
                && $request->data()['i'] === 'tt0903747'
                && $request->data()['plot'] === 'full',
        );

        Http::assertSentCount(1);
    }

    public function test_find_by_id_caches_successful_response(): void
    {
        $omdbResponse = [
            'Title' => 'Breaking Bad',
            'imdbID' => 'tt0903747',
            'Response' => 'True',
        ];

        Http::preventStrayRequests();
        Http::fake([
            self::OMDB_URL_PATTERN => Http::response($omdbResponse),
        ]);

        $omdbClient = app(OmdbClient::class);

        $firstResult = $omdbClient->findById('tt0903747');
        $secondResult = $omdbClient->findById('tt0903747');

        $this->assertSame('tt0903747', $firstResult->imdbId);
        $this->assertSame('Breaking Bad', $firstResult->title);

        $this->assertSame('tt0903747', $secondResult->imdbId);
        $this->assertSame('Breaking Bad', $secondResult->title);

        Http::assertSentCount(1);
    }

    public function test_find_by_id_failed_responses_are_not_cached(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            self::OMDB_URL_PATTERN => Http::response([
                'Response' => 'False',
                'Error' => self::INVALID_API_KEY_MESSAGE,
            ]),
        ]);

        $omdbClient = app(OmdbClient::class);

        $this->assertThrows(
            static fn () => $omdbClient->findById('tt0903747'),
            RuntimeException::class,
            self::INVALID_API_KEY_MESSAGE,
        );
        $this->assertThrows(
            static fn () => $omdbClient->findById('tt0903747'),
            RuntimeException::class,
            self::INVALID_API_KEY_MESSAGE,
        );

        Http::assertSentCount(2);
    }
}
