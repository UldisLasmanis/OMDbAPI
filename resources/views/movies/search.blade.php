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

    @if ($moviesFoundTotal > 1000)
        <p class="info-msg">
            We found {{ number_format($moviesFoundTotal) }} matches.
            You can browse the first 1,000 results. Try a more specific search to narrow the results.
        </p>
    @endif

    @if ($errors->any())
        <ul class="validation-errors">
            @foreach ($errors->all() as $validationError)
                <li>{{ $validationError }}</li>
            @endforeach
        </ul>
    @endif

    @if (!empty($error))
        <p class="error-msg">
            {{ $error }}
        </p>
    @elseif ($movies?->count())
        <section aria-labelledby="results-heading">
            <h2 id="results-heading" class="section-title">
                Search results
            </h2>

            <div class="movies">
                @foreach ($movies as $movie)
                    <article class="movie">
                        @if (($movie['Poster'] ?? 'N/A') !== 'N/A')
                            <img
                                class="movie-poster"
                                src="{{ $movie['Poster'] }}"
                                alt="Poster for {{ $movie['Title'] }}"
                                loading="lazy"
                            >
                        @else
                            <div class="poster-placeholder">
                                No poster available
                            </div>
                        @endif

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
    @elseif (request()->filled('query') && !$errors->any())
        <p class="info-msg">
            No movies were found containing “{{ request('query') }}”
        </p>
    @endif
@endsection
