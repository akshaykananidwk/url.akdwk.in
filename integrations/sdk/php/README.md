# Shortl PHP SDK

A minimal, dependency-free PHP client for the URL shortener REST API (v1). It
uses cURL directly, so there is nothing to install beyond the package itself.

## Requirements

- PHP 8.1+
- `ext-curl`, `ext-json`

## Install

```bash
composer require yoursite/shortl-sdk
```

Or drop the `src/` directory into your project and register the PSR-4
autoload prefix `ShortlSdk\` → `src/`.

## Authentication

Requests are authenticated with an API key sent as a Bearer token:

```
Authorization: Bearer sk_xxxxxxxx
```

Generate a key from the **Developers** page in your dashboard. API access
requires a plan that includes the `api` feature.

## Usage

```php
require 'vendor/autoload.php';

use ShortlSdk\Client;
use ShortlSdk\ApiException;

// Pass your site root — "/api/v1" is appended automatically.
$client = new Client('sk_live_xxxxxxxx', 'https://example.com');

try {
    // Shorten a URL
    $link = $client->shorten('https://laravel.com', [
        'alias' => 'laravel',
        'title' => 'Laravel',
    ]);
    echo $link['short_url'], PHP_EOL;

    // List links (supports q, space_id, per_page)
    $page = $client->list(['per_page' => 50]);
    echo $page['meta']['total'], ' links', PHP_EOL;

    // Fetch one link by id
    $one = $client->get((string) $link['id']);

    // Analytics
    $stats = $client->stats((string) $link['id']);
    echo $stats['totals']['clicks'] ?? 0, ' clicks', PHP_EOL;

    // Delete
    $client->delete((string) $link['id']);
} catch (ApiException $e) {
    // HTTP status via getCode(); decoded error body via getResponse()
    fwrite(STDERR, "API error {$e->getCode()}: {$e->getMessage()}\n");
}
```

## Methods

| Method | Endpoint |
| --- | --- |
| `shorten(string $url, array $opts = [])` | `POST /api/v1/links` |
| `get(string $alias)` | `GET /api/v1/links/{link}` |
| `list(array $params = [])` | `GET /api/v1/links` |
| `stats(string $alias)` | `GET /api/v1/links/{link}/stats` |
| `delete(string $alias)` | `DELETE /api/v1/links/{link}` |

The link identifier is the numeric `id` returned in every link resource.
