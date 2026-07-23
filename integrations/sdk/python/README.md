# Shortl Python SDK

A zero-dependency client for the URL shortener REST API (v1). It uses only the
Python standard library (`urllib`) — there is no `requests` dependency.

## Requirements

- Python 3.7+

## Install

Copy `shortl_sdk.py` into your project (or your `PYTHONPATH`) and import it.

## Authentication

Requests are authenticated with an API key sent as a Bearer token:

```
Authorization: Bearer sk_xxxxxxxx
```

Generate a key from the **Developers** page in your dashboard. API access
requires a plan that includes the `api` feature.

## Usage

```python
from shortl_sdk import Client, ShortlError

# Pass your site root — "/api/v1" is appended automatically.
client = Client("sk_live_xxxxxxxx", "https://example.com")

try:
    # Shorten a URL
    link = client.shorten("https://laravel.com", alias="laravel", title="Laravel")
    print(link["short_url"])

    # List links (supports q, space_id, per_page)
    page = client.list_links(per_page=50)
    print(page["meta"]["total"], "links")

    # Fetch one link by id
    one = client.get(link["id"])

    # Analytics
    stats = client.stats(link["id"])
    print(stats["totals"])

    # Delete
    client.delete(link["id"])
except ShortlError as err:
    print(f"API error {err.status}: {err} — {err.body}")
```

## Methods

| Method | Endpoint |
| --- | --- |
| `shorten(url, **opts)` | `POST /api/v1/links` |
| `get(alias)` | `GET /api/v1/links/{link}` |
| `list_links(**params)` | `GET /api/v1/links` |
| `stats(alias)` | `GET /api/v1/links/{link}/stats` |
| `delete(alias)` | `DELETE /api/v1/links/{link}` |

The link identifier is the numeric `id` returned in every link resource.
