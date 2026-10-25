<?php

namespace Tests\Feature\Http\Controllers;

use App\Data\MovieDetails;
use Illuminate\Http\Client\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class MovieControllerTest extends TestCase
{
    private const string OMDB_BASE_URL = 'https://www.omdbapi.com';
    private const string OMDB_URL_PATTERN = self::OMDB_BASE_URL.'/*';

    private const string SEARCH_FAILED_CLIENT_MESSAGE = 'The service is temporarily unavailable. Please try again later.';
    private const string SHOW_FAILED_CLIENT_MESSAGE = 'Movie details are temporarily unavailable. Please try again later.';
    private const string INVALID_API_KEY_MESSAGE = 'Invalid API key!';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.omdb.base_url' => self::OMDB_BASE_URL,
        ]);
    }

    public function test_search_displays_movies_returned_by_omdb(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            self::OMDB_URL_PATTERN => Http::response([
                'Search' => [
                    [
                        'Title' => 'The Matrix',
                        'Year' => '1999',
                        'imdbID' => 'tt0133093',
                        'Type' => 'movie',
                        'Poster' => 'N/A',
                    ],
                    [
                        'Title' => 'The Matrix Reloaded',
                        'Year' => '2003',
                        'imdbID' => 'tt0234215',
                        'Type' => 'movie',
                        'Poster' => 'N/A',
                    ],
                ],
                'totalResults' => '2',
                'Response' => 'True',
            ]),
        ]);

        $response = $this->get(route('movies.search', [
            'query' => 'Matrix',
        ]));

        $response
            ->assertOk()
            ->assertViewIs('movies.search')
            ->assertSeeText('The Matrix')
            ->assertSeeText('1999 · Movie')
            ->assertSeeText('The Matrix Reloaded')
            ->assertSeeText('2003 · Movie')
            ->assertSee(route('movies.show', [
                'imdbId' => 'tt0133093',
            ]))
            ->assertSee(route('movies.show', [
                'imdbId' => 'tt0234215',
            ]));

        Http::assertSent(
            static fn (Request $request): bool => $request->data()['s'] === 'Matrix'
                && $request->data()['page'] === 1,
        );
    }

    public function test_search_displays_an_empty_state_when_no_movies_are_found(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            self::OMDB_URL_PATTERN => Http::response([
                'Response' => 'False',
                'Error' => 'Movie not found!',
            ]),
        ]);

        $response = $this->get(route('movies.search', [
            'query' => 'Missing movie',
        ]));

        $response
            ->assertOk()
            ->assertViewIs('movies.search')
            ->assertSeeText(
                'No movies were found containing “Missing movie”',
            )
            ->assertDontSeeText('Movie not found!')
            ->assertDontSeeText('Search results');

        Http::assertSent(
            static fn (Request $request): bool => $request->data()['s'] === 'Missing movie'
                && $request->data()['page'] === 1,
        );
    }

    #[DataProvider('invalidSearchParameters')]
    public function test_search_rejects_invalid_parameters_without_calling_omdb(
        array $parameters,
        string $invalidField,
    ): void {
        Http::preventStrayRequests();

        $response = $this->get(route('movies.search', $parameters));

        $response
            ->assertRedirect()
            ->assertSessionHasErrors($invalidField);

        Http::assertNothingSent();
    }

    public static function invalidSearchParameters(): array
    {
        return [
            'empty query' => [
                ['query' => ''],
                'query',
            ],
            'query contains only whitespace' => [
                ['query' => '   '],
                'query',
            ],
            'query is not a string' => [
                ['query' => ['Matrix']],
                'query',
            ],
            'query longer than 100 characters' => [
                ['query' => str_repeat('a', 101)],
                'query',
            ],
            'page is not an integer' => [
                ['query' => 'Matrix', 'page' => 'second'],
                'page',
            ],
            'page below OMDb minimum' => [
                ['query' => 'Matrix', 'page' => 0],
                'page',
            ],
            'page above OMDb maximum' => [
                ['query' => 'Matrix', 'page' => 101],
                'page',
            ],
        ];
    }

    public function test_search_displays_generic_error_without_exposing_omdb_error(): void
    {
        Exceptions::fake();

        Http::preventStrayRequests();
        Http::fake([
            self::OMDB_URL_PATTERN => Http::response([
                'Response' => 'False',
                'Error' => self::INVALID_API_KEY_MESSAGE,
            ]),
        ]);

        $response = $this->get(route('movies.search', [
            'query' => 'Matrix',
        ]));

        $response
            ->assertOk()
            ->assertSeeText(self::SEARCH_FAILED_CLIENT_MESSAGE)
            ->assertDontSeeText(self::INVALID_API_KEY_MESSAGE);

        Http::assertSentCount(1);

        Exceptions::assertReported(RuntimeException::class);
    }

    #[DataProvider('recentSearchHistoryScenarios')]
    public function test_search_maintains_recent_search_history(
        array $searchQueries,
        array $expectedRecentSearches,
    ): void {
        Http::preventStrayRequests();
        Http::fake([
            self::OMDB_URL_PATTERN => Http::response([
                'Search' => [],
                'totalResults' => '0',
                'Response' => 'True',
            ]),
        ]);

        foreach ($searchQueries as $query) {
            $this->get(route('movies.search', [
                'query' => $query,
            ]))->assertOk();
        }

        $this->assertSame(
            $expectedRecentSearches,
            session()->get('recent_movie_searches'),
        );
    }

    public static function recentSearchHistoryScenarios(): array
    {
        return [
            'first search is stored' => [
                ['Matrix'],
                ['Matrix'],
            ],
            'newest searches are stored first' => [
                [
                    'Matrix',
                    'Shrek',
                    'Alien',
                ],
                [
                    'Alien',
                    'Shrek',
                    'Matrix',
                ],
            ],
            'only five most recent searches are retained' => [
                [
                    'Matrix',
                    'Shrek',
                    'Alien',
                    'Avatar',
                    'Titanic',
                    'Inception',
                ],
                [
                    'Inception',
                    'Titanic',
                    'Avatar',
                    'Alien',
                    'Shrek',
                ],
            ],
            'duplicate searches are case insensitive and move to the beginning' => [
                [
                    'Matrix',
                    'Shrek',
                    'Alien',
                    'MATRIX',
                ],
                [
                    'MATRIX',
                    'Alien',
                    'Shrek',
                ],
            ],
        ];
    }

    public function test_search_browsing_result_pages_does_not_add_a_recent_search(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            self::OMDB_URL_PATTERN => Http::response([
                'Search' => [],
                'totalResults' => '0',
                'Response' => 'True',
            ]),
        ]);

        $firstResponse = $this->get(route('movies.search', [
            'query' => 'Shrek',
        ]));

        $firstResponse
            ->assertOk()
            ->assertSessionHas('recent_movie_searches', [
                'Shrek',
            ]);

        $secondResponse = $this->get(route('movies.search', [
            'query' => 'Matrix',
            'page' => 2,
        ]));

        $secondResponse
            ->assertOk()
            ->assertSessionHas('recent_movie_searches', [
                'Shrek',
            ]);

        Http::assertSent(
            static fn (Request $request): bool => $request->data()['s'] === 'Matrix'
                && $request->data()['page'] === 2,
        );

        Http::assertSentCount(2);
    }

    public function test_search_limits_accessible_results_to_omdb_maximum(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            self::OMDB_URL_PATTERN => Http::response([
                'Search' => [[
                    'Title' => 'The Matrix',
                    'Year' => '1999',
                    'imdbID' => 'tt0133093',
                    'Type' => 'movie',
                    'Poster' => 'N/A',
                ]],
                'totalResults' => '1500',
                'Response' => 'True',
            ]),
        ]);

        $response = $this->get(route('movies.search', [
            'query' => 'Matrix',
        ]));

        $response
            ->assertOk()
            ->assertViewIs('movies.search')
            ->assertViewHas(
                'movies',
                static fn (LengthAwarePaginator $movies): bool => $movies->total() === 1000,
            )
            ->assertViewHas('moviesFoundTotal', 1500)
            ->assertViewHas('moviesAccessibleLimit', 1000)
            ->assertViewHas('hasInaccessibleResults', true)
            ->assertSeeText('We found 1,500 matches.')
            ->assertSeeText('You can browse the first 1,000 results.')
            ->assertSeeText('Try a more specific search to narrow the results.');
    }

    public function test_show_displays_movie_details_returned_by_omdb(): void
    {
        $movieDetails = [
            'Title' => 'Breaking Bad',
            'Year' => '2008–2013',
            'Rated' => 'TV-MA',
            'Released' => '20 Jan 2008',
            'Runtime' => '49 min',
            'Genre' => 'Crime, Drama, Thriller',
            'Director' => 'N/A',
            'Writer' => 'Vince Gilligan',
            'Actors' => 'Bryan Cranston, Aaron Paul, Anna Gunn',
            'Plot' => 'A chemistry teacher turns to manufacturing methamphetamine.',
            'Language' => 'English, Spanish',
            'Country' => 'United States',
            'Awards' => 'Won 16 Primetime Emmys. 172 wins & 269 nominations total',
            'Poster' => 'https://example.com/breaking-bad.jpg',
            'Ratings' => [
                [
                    'Source' => 'Internet Movie Database',
                    'Value' => '9.5/10',
                ],
                [
                    'Source' => 'Rotten Tomatoes',
                    'Value' => '96%',
                ],
            ],
            'Metascore' => 'N/A',
            'imdbRating' => '9.5',
            'imdbVotes' => '2,671,962',
            'imdbID' => 'tt0903747',
            'Type' => 'series',
            'totalSeasons' => '5',
            'Response' => 'True',
        ];

        Http::preventStrayRequests();
        Http::fake([
            self::OMDB_URL_PATTERN => Http::response($movieDetails),
        ]);

        $response = $this->get(route('movies.show', [
            'imdbId' => 'tt0903747',
        ]));

        $response
            ->assertOk()
            ->assertViewIs('movies.show')
            ->assertViewHas(
                'movie',
                static fn (MovieDetails $movie): bool => $movie->imdbId === 'tt0903747',
            )
            ->assertSeeText('Breaking Bad')
            ->assertSeeText('2008–2013')
            ->assertSeeText('Series')
            ->assertSeeText('49 min')
            ->assertSeeText('IMDb')
            ->assertSeeText('9.5/10')
            ->assertSeeText('(2,671,962)')
            ->assertSeeText('96%')
            ->assertSeeText('A chemistry teacher turns to manufacturing methamphetamine.')
            ->assertSeeText('Crime, Drama, Thriller')
            ->assertSeeText('Vince Gilligan')
            ->assertSeeText('Bryan Cranston, Aaron Paul, Anna Gunn')
            ->assertSeeText('English, Spanish')
            ->assertSeeText('United States')
            ->assertSeeText('Won 16 Primetime Emmys. 172 wins & 269 nominations total')
            ->assertDontSeeText('Director')
            ->assertDontSeeText('N/A')
            ->assertSee('https://example.com/breaking-bad.jpg');

        Http::assertSent(
            static fn (Request $request): bool => $request->data()['i'] === 'tt0903747'
                && $request->data()['plot'] === 'full',
        );
    }

    public function test_show_displays_generic_error_without_exposing_omdb_error(): void
    {
        Exceptions::fake();

        Http::preventStrayRequests();
        Http::fake([
            self::OMDB_URL_PATTERN => Http::response([
                'Response' => 'False',
                'Error' => self::INVALID_API_KEY_MESSAGE,
            ]),
        ]);

        $response = $this->get(route('movies.show', [
            'imdbId' => 'tt0903747',
        ]));

        $response
            ->assertOk()
            ->assertViewIs('movies.show')
            ->assertViewHas('movie', null)
            ->assertViewHas('error', self::SHOW_FAILED_CLIENT_MESSAGE)
            ->assertSeeText(self::SHOW_FAILED_CLIENT_MESSAGE)
            ->assertDontSeeText(self::INVALID_API_KEY_MESSAGE);

        Http::assertSentCount(1);

        Exceptions::assertReported(RuntimeException::class);
    }

    #[DataProvider('invalidImdbIds')]
    public function test_show_rejects_invalid_imdb_ids_without_calling_omdb(
        string $imdbId,
    ): void {
        Http::preventStrayRequests();

        $response = $this->get(route('movies.show', [
            'imdbId' => $imdbId,
        ]));

        $response->assertNotFound();

        Http::assertNothingSent();
    }

    public static function invalidImdbIds(): array
    {
        return [
            'missing tt prefix' => [
                '0903747',
            ],
            'uppercase prefix' => [
                'TT0903747',
            ],
            'non-numeric identifier' => [
                'ttABC',
            ],
            'missing numeric identifier' => [
                'tt',
            ],
            'unexpected suffix' => [
                'tt0903747-extra',
            ],
        ];
    }
}
