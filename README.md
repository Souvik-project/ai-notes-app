# AI Notes App

A Laravel 12 notes management system built for the PHP Backend + AI Usage assignment. It includes JSON CRUD APIs, pagination, validation, rate limiting, a local semantic-search implementation, AI-style extractive summaries, a simple frontend UI, OpenAPI notes, and feature tests.

## Setup

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate
npm run build
php artisan serve
```

The included `.env` uses SQLite for easy local review. For MySQL or PostgreSQL, set `DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`, then run `php artisan migrate`.

## API Documentation

Base URL: `/api`

| Method | Endpoint | Description |
| --- | --- | --- |
| GET | `/notes?page=1&limit=10` | Paginated notes list |
| POST | `/notes` | Create a note |
| GET | `/notes/{id}` | Get one note |
| PUT/PATCH | `/notes/{id}` | Update a note |
| DELETE | `/notes/{id}` | Delete a note |
| GET | `/notes/search?q=project&limit=10` | Semantic note search |
| POST | `/notes/{id}/summary` | Generate and store note summary |

## Requirement Coverage

- Notes CRUD APIs: `POST /api/notes`, `GET /api/notes`, `GET /api/notes/{id}`, `PUT/PATCH /api/notes/{id}`, and `DELETE /api/notes/{id}`.
- Pagination: `GET /api/notes?page=1&limit=10` returns pagination metadata in `meta.pagination`.
- Semantic search: `GET /api/notes/search?q=keyword&limit=10` ranks notes using persisted token vectors and cosine similarity.
- AI summary endpoint: `POST /api/notes/{id}/summary` generates and stores an extractive AI-style summary.
- Frontend UI: `/` serves the AI-generated notes workspace for create, edit, delete, search, pagination, and summaries.
- Clean API responses: success and API validation/not-found errors use structured JSON envelopes.

Create/update body:

```json
{
  "title": "Sprint planning",
  "content": "Discuss API design, test coverage, and release risks.",
  "tags": ["work", "planning"]
}
```

Response shape:

```json
{
  "success": true,
  "message": "OK",
  "data": {},
  "meta": {}
}
```

## Database Schema

`notes`

| Column | Type | Purpose |
| --- | --- | --- |
| `id` | bigint | Primary key |
| `title` | string | Note title |
| `content` | text | Note body |
| `tags` | json nullable | User tags |
| `summary` | text nullable | Generated summary |
| `search_vector` | json nullable | Normalized token vector for semantic scoring |
| `created_at`, `updated_at` | timestamps | Audit timestamps |

## AI Features

Semantic search is implemented in `App\Services\LocalAiNotesService`. It tokenizes the note title/content, removes stop words, creates normalized term-frequency vectors, and ranks notes with cosine similarity. This satisfies the vector-search style requirement without requiring an external API key.

The summary endpoint uses an extractive summarizer: it splits note content into sentences, ranks each sentence against the full note vector, and returns the highest-signal sentences in original order. The service is isolated so it can later be replaced with OpenAI embeddings and summaries.

## AI Tools Used

Codex/ChatGPT was used to interpret the assignment PDF, design the Laravel implementation, generate the frontend UI, and create tests/docs.

Prompts used:

- "Build the PHP AI Notes assignment perfectly and change the whole project accordingly."
- "Extract the assignment PDF and implement CRUD APIs, semantic search, summary endpoint, frontend, docs, and tests."

Generated code was validated by running migrations/tests and manually checking endpoint behavior.

## Architecture

- `routes/api.php`: API routes protected by Laravel throttle middleware.
- `app/Http/Controllers/Api/NoteController.php`: request validation, JSON responses, CRUD, search, summary.
- `app/Models/Note.php`: Eloquent model with JSON casts.
- `app/Services/LocalAiNotesService.php`: semantic vectorization and summarization logic.
- `resources/views/notes.blade.php`, `resources/js/app.js`, `resources/css/app.css`: AI-generated frontend UI.
- `public/openapi.json`: lightweight OpenAPI entry points.

## Security

- Eloquent ORM and query builder prevent SQL injection.
- Request validation is applied to write, list, and search endpoints.
- API routes use Laravel `throttle:api` rate limiting.
- API validation errors return HTTP 422, missing resources return HTTP 404, created notes return HTTP 201, and normal success responses return HTTP 200.
- API responses avoid exposing SQL or stack details in normal validation/not-found flows.

## Testing

```bash
php artisan test
```

Tests cover CRUD, validation, pagination, semantic search, and summary generation.
