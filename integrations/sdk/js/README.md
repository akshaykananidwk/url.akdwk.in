# Shortl JS SDK

A zero-dependency client for the URL shortener REST API (v1). It uses the
global `fetch`, so it runs in **Node 18+** and modern **browsers** with no
build step and nothing to install at runtime.

## Install

```bash
npm install @yoursite/shortl-sdk
```

Or copy `index.js` into your project and import it directly.

## Authentication

Requests are authenticated with an API key sent as a Bearer token:

```
Authorization: Bearer sk_xxxxxxxx
```

Generate a key from the **Developers** page in your dashboard. API access
requires a plan that includes the `api` feature.

## Usage

```js
import { ShortlClient, ShortlError } from '@yoursite/shortl-sdk';

// Pass your site root — "/api/v1" is appended automatically.
const client = new ShortlClient('sk_live_xxxxxxxx', 'https://example.com');

try {
  // Shorten a URL
  const link = await client.shorten('https://laravel.com', {
    alias: 'laravel',
    title: 'Laravel',
  });
  console.log(link.short_url);

  // List links (supports q, space_id, per_page)
  const page = await client.list({ per_page: 50 });
  console.log(page.meta.total, 'links');

  // Fetch one link by id
  const one = await client.get(link.id);

  // Analytics
  const stats = await client.stats(link.id);
  console.log(stats.totals);

  // Delete
  await client.remove(link.id);
} catch (err) {
  if (err instanceof ShortlError) {
    console.error(`API error ${err.status}:`, err.message, err.body);
  } else {
    throw err;
  }
}
```

## Methods

| Method | Endpoint |
| --- | --- |
| `shorten(url, opts?)` | `POST /api/v1/links` |
| `get(alias)` | `GET /api/v1/links/{link}` |
| `list(params?)` | `GET /api/v1/links` |
| `stats(alias)` | `GET /api/v1/links/{link}/stats` |
| `remove(alias)` | `DELETE /api/v1/links/{link}` |

The link identifier is the numeric `id` returned in every link resource.

## Browser use

Because the SDK ships an API key, only use it in the browser for trusted,
first-party contexts (e.g. an internal dashboard). Never embed a secret key in
a public web page.
