@extends('layouts.movies')

@section('content')
    <h1 class="page-title">Movie Search</h1>

    <p class="introduction">
        Search for movies, series, and episodes using OMDb
    </p>

    <form
        class="search-form"
        method="GET"
        action="{{ route('movies.search') }}"
    >
        <label for="query" class="sr-only">Movie title</label>

        <input
            id="query"
            type="search"
            name="query"
            value="{{ request('query') }}"
            placeholder="Enter a movie title, for example Prison Break"
            maxlength="100"
            required
            autofocus
        >

        <button class="button" type="submit">
            Search
        </button>
    </form>

    @php
        $hasNoResults = $movies !== null
            && $movies->isEmpty()
            && request()->filled('query')
            && empty($error)
            && !$errors->any();

        $hasMessages = $hasInaccessibleResults
            || $errors->any()
            || !empty($error)
            || $hasNoResults;
    @endphp

    @if ($hasMessages)
        <div class="message-block">
            @if ($hasInaccessibleResults)
                <p class="info-msg">
                    We found {{ number_format($moviesFoundTotal) }} matches.
                    You can browse the first {{ number_format($moviesAccessibleLimit) }} results.
                    Try a more specific search to narrow the results.
                </p>
            @endif

            @if ($errors->any())
                <ul class="error-msg">
                    @foreach ($errors->all() as $validationError)
                        <li>{{ $validationError }}</li>
                    @endforeach
                </ul>
            @endif

            @if (! empty($error))
                <p class="error-msg">
                    {{ $error }}
                </p>
            @endif

            @if ($hasNoResults)
                <p class="info-msg">
                    No movies were found containing “{{ request('query') }}”
                </p>
            @endif
        </div>
    @endif

    @if ($recentSearches)
        <details class="recent-searches">
            <summary class="recent-searches-toggle">
                Recent searches
            </summary>

            <ul class="recent-searches-list">
                @foreach ($recentSearches as $recentQuery)
                    <li>
                        <a href="{{ route('movies.search', ['query' => $recentQuery]) }}">
                            {{ $recentQuery }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </details>
    @endif

    @if ($movies?->count())
        <section aria-labelledby="results-heading">
            <h2 id="results-heading" class="section-title">
                Search results
            </h2>

            <div class="movies">
                @foreach ($movies as $movie)
                    <article class="movie">
                        <a
                            class="movie-link"
                            href="{{ route('movies.show', ['imdbId' => $movie['imdbID']]) }}"
                            data-movie-link
                        >
                            <div class="movie-poster-wrapper">
                                @if (($movie['Poster'] ?? 'N/A') !== 'N/A')
                                    <img
                                        class="movie-poster"
                                        src="{{ $movie['Poster'] }}"
                                        alt="Poster for {{ $movie['Title'] }}"
                                        loading="lazy"
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

                                <span class="movie-poster-overlay" aria-hidden="true">
                                    <span class="movie-view-button">View</span>
                                </span>
                            </div>
                        </a>
                        <div class="movie-details">
                            <h3 class="movie-title">
                                {{ $movie['Title'] }}
                            </h3>

                            <p class="movie-meta">
                                {{ $movie['Year'] }} · {{ ucfirst($movie['Type']) }}
                            </p>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <div class="pagination-wrapper">
            {{ $movies->onEachSide(1)->links() }}
        </div>
    @endif
@endsection
