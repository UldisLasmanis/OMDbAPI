@extends('layouts.movies')

@section('content')
    <a class="back-link" href="{{ route('movies.search') }}" data-back-to-search>
        ← Back to search
    </a>

    @if (! empty($error))
        <p class="error-msg">
            {{ $error }}
        </p>
    @elseif ($movie)
        <article class="movie-page">
            <div class="movie-page-poster-wrapper">
                @if (($movie['Poster'] ?? 'N/A') !== 'N/A')
                    <img
                        class="movie-page-poster"
                        src="{{ $movie['Poster'] }}"
                        alt="Poster for {{ $movie['Title'] }}"
                        data-movie-poster
                    >

                    <div class="poster-placeholder" data-poster-placeholder hidden>
                        No poster available
                    </div>
                @else
                    <div class="poster-placeholder">
                        No poster available
                    </div>
                @endif
            </div>

            <div class="movie-page-content">
                <h1 class="page-title movie-page-title">
                    {{ $movie['Title'] }}
                </h1>

                @php
                    $ratings = array_column($movie['Ratings'] ?? [], 'Value', 'Source');
                    $rottenTomatoesRating = $ratings['Rotten Tomatoes'] ?? null;
                    $hasImdbRating = ($movie['imdbRating'] ?? 'N/A') !== 'N/A';
                @endphp

                <p class="movie-page-summary">
                    {{ $movie['Year'] }}

                    @if (($movie['Type'] ?? 'N/A') !== 'N/A')
                        · {{ ucfirst($movie['Type']) }}
                    @endif

                    @if (($movie['Runtime'] ?? 'N/A') !== 'N/A')
                        · {{ $movie['Runtime'] }}
                    @endif
                </p>

                @if ($hasImdbRating || $rottenTomatoesRating)
                    <span>
                        @if ($hasImdbRating)
                            IMDb
                            {{ $movie['imdbRating'] }}/10
                            <span class="imdb-rating-star" aria-hidden="true">★</span>
                            @if (($movie['imdbVotes'] ?? 'N/A') !== 'N/A')
                                ({{ $movie['imdbVotes'] }})
                            @endif
                        @endif

                        @if ($hasImdbRating && $rottenTomatoesRating)
                            ·
                        @endif

                        @if ($rottenTomatoesRating)
                            <span aria-label="Rotten Tomatoes rating: {{ $rottenTomatoesRating }}">
                                <span aria-hidden="true">🍅</span> {{ $rottenTomatoesRating }}
                            </span>
                        @endif
                    </span>
                @endif

                @if (($movie['Plot'] ?? 'N/A') !== 'N/A')
                    <p class="movie-page-plot">
                        {{ $movie['Plot'] }}
                    </p>
                @endif

                <div class="movie-facts">
                    @foreach ([
                        'Genre' => 'Genre',
                        'Director' => 'Director',
                        'Writer' => 'Writer',
                        'Actors' => 'Actors',
                        'Language' => 'Language',
                        'Country' => 'Country',
                        'Awards' => 'Awards',
                    ] as $field => $label)
                        @if (($movie[$field] ?? 'N/A') !== 'N/A')
                            <div class="movie-fact">
                                <strong>{{ $label }}</strong>
                                <span>{{ $movie[$field] }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </article>
    @endif
@endsection
