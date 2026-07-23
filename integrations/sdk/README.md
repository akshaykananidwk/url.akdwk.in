# Official SDKs

Client libraries for the URL shortener REST API (**v1**). Each SDK is a thin,
dependency-light wrapper over the same HTTP endpoints — pick the one that
matches your stack.

| Language | Directory | Runtime deps |
| --- | --- | --- |
| PHP | [`php/`](./php) | none (uses cURL) |
| JavaScript / Node | [`js/`](./js) | none (uses `fetch`) |
| Python | [`python/`](./python) | none (stdlib `urllib`) |

## Base URL

All requests go to the `v1` prefix under `/api`:

```
https://your-domain.com/api/v1
```

Every SDK accepts your **site root** (e.g. `https://your-domain.com`) and
appends `/api/v1` for you.

## Getting an API key

Create and manage keys from the **Developers** page in your dashboard. API
access requires a plan that includes the `api` feature. Keys look like
`sk_...` and are shown once at creation — store them securely.

## Authentication

Send the key as a **Bearer token** on every request:

```
Authorization: Bearer sk_xxxxxxxx
```

(The API also accepts the key in an `X-Api-Key` header, but the SDKs use the
Bearer scheme.)

## Endpoints the SDKs cover

| Operation | HTTP |
| --- | --- |
| Shorten a URL | `POST /api/v1/links` |
| List links | `GET /api/v1/links` |
| Get a link | `GET /api/v1/links/{link}` |
| Link stats | `GET /api/v1/links/{link}/stats` |
| Delete a link | `DELETE /api/v1/links/{link}` |

When shortening, the destination URL is sent as the `destination` field.
Optional fields include `alias`, `title`, `domain_id`, `space_id`, `password`,
`expires_at`, `max_clicks`, `utm`, and `targeting`.

The full REST surface (including `/me`, `/spaces`, `/domains`, and QR codes) is
documented at `/developers/docs`, with a Postman collection in
`docs/postman_collection.json`.

## Rate limits

Responses include `X-RateLimit-Limit` and `X-RateLimit-Remaining` headers. When
you exceed your plan's rate, the API returns `429` with a `Retry-After` header;
the SDKs surface this as an error you can catch.
