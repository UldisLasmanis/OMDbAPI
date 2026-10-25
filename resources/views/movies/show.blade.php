@php
    /** @var \App\Data\MovieDetails|null $movie */
@endphp

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
                @if ($movie->poster)
                    <img
                        class="movie-page-poster"
                        src="{{ $movie->poster }}"
                        alt="Poster for {{ $movie->title }}"
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
                    {{ $movie->title }}
                </h1>

                <p class="movie-page-summary">
                    {{ $movie->year }}

                    @if ($movie->type)
                        · {{ ucfirst($movie->type) }}
                    @endif

                    @if ($movie->runtime)
                        · {{ $movie->runtime }}
                    @endif
                </p>

                @if ($movie->imdbRating || $movie->rottenTomatoesRating)
                    <span>
                        @if ($movie->imdbRating)
                            IMDb
                            {{ $movie->imdbRating }}/10
                            <span class="imdb-rating-star" aria-hidden="true">★</span>
                            @if ($movie->imdbVotes)
                                ({{ $movie->imdbVotes }})
                            @endif
                        @endif

                        @if ($movie->imdbRating && $movie->rottenTomatoesRating)
                            ·
                        @endif

                        @if ($movie->rottenTomatoesRating)
                            <span aria-label="Rotten Tomatoes rating: {{ $movie->rottenTomatoesRating }}">
                                <span aria-hidden="true">🍅</span> {{ $movie->rottenTomatoesRating }}
                            </span>
                        @endif
                    </span>
                @endif

                @if ($movie->plot)
                    <p class="movie-page-plot">
                        {{ $movie->plot }}
                    </p>
                @endif

                <div class="movie-facts">
                    @foreach ([
                        'Genre' => $movie->genre,
                        'Director' => $movie->director,
                        'Writer' => $movie->writer,
                        'Actors' => $movie->actors,
                        'Language' => $movie->language,
                        'Country' => $movie->country,
                        'Awards' => $movie->awards,
                    ] as $label => $value)
                        @if ($value)
                            <div class="movie-fact">
                                <strong>{{ $label }}</strong>
                                <span>{{ $value }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </article>
    @endif
@endsection
