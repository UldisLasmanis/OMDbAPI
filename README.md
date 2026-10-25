# OMDb Movie Search

A Laravel application for searching movies, series, and episodes using the
[OMDb API](https://www.omdbapi.com/). It includes paginated results, recent
searches, cached API responses, and individual title pages.

## Setup

Requirements: PHP 8.3+, Composer, Node.js, npm, and an
[OMDb API key](https://www.omdbapi.com/apikey.aspx).

```bash
git clone <repository-url>
cd OMDbAPI
touch database/database.sqlite
composer run setup
```

Add the OMDb API key to `.env`:

```dotenv
OMDB_API_KEY=your_api_key
```

Start the application:

```bash
composer run dev
```

Open [http://localhost:8000](http://localhost:8000).

## Tests

```bash
php artisan test --compact
```

The tests use fake OMDb responses and do not make real API requests.
