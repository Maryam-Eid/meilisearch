# Article search

A Laravel app that searches published articles with [Laravel Scout](https://laravel.com/docs/scout) and [Meilisearch](https://www.meilisearch.com/). Open `/search` to try it. No login is required.

## What you can do

- Results update while you type.
- Matching words in the title and teaser are highlighted.
- The current page of titles is offered as suggestions.
- Filter by author, with the number of matches beside each name.
- Limit results to the past week, month, or year.
- Sort by newest or oldest. The default order is relevance.
- A close spelling with no hits shows **Did you mean**. Choosing it runs the corrected search.
- The last five searches are kept in this browser.
- Drafts are stored in the database and left out of the index.

## Demo

https://github.com/user-attachments/assets/2abc5de9-dadb-4639-bc12-0fbaa4057b8c

## Requirements

- PHP 8.3 or newer
- Composer
- Node.js and npm
- MySQL
- The Meilisearch binary

## Setup

```bash
composer install
npm install
npm run build
cp .env.example .env
php artisan key:generate
```

`.env.example` already points at the local MySQL database `meilisearch` and at Meilisearch on port 7700.

Start Meilisearch with the same key:

```bash
meilisearch --master-key masterKey --db-path data.ms --http-addr 127.0.0.1:7700
```

Create the tables, load the sample articles, and build the index:

```bash
php artisan migrate --seed
php artisan scout:sync-index-settings
php artisan scout:import "App\Models\Article"
php artisan serve
```

Then open [http://127.0.0.1:8000/search](http://127.0.0.1:8000/search).

For frontend changes during development, run `npm run dev` instead of `npm run build`.

## Sample data

Seeding creates six authors and sixteen English articles. Each author gets a different number of articles, and a few articles stay unpublished.

| Name | Email |
| --- | --- |
| James Smith | james.smith@example.com |
| Mary Johnson | mary.johnson@example.com |
| John Williams | john.williams@example.com |
| Sarah Brown | sarah.brown@example.com |
| Michael Davis | michael.davis@example.com |
| Emily Wilson | emily.wilson@example.com |

Every seeded account uses the password `password`.

Search `beans` for a hit, `beens` for a spelling suggestion, and `Saturday` for a draft that should not appear.

## Tests

Tests use an in-memory SQLite database and Scout's collection driver. They do not call Meilisearch.

```bash
php artisan config:clear
php artisan test
```

Clear the cached config first. A cached config that still points at MySQL will make the tests refresh that database.
