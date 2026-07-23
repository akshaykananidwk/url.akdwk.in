# Zapier / Make.com integration

Shortl exposes a standard REST API, so you can wire it into Zapier, Make.com
(Integromat), n8n, Pipedream, or any automation tool using the generic
"HTTP / Webhooks" app — no custom app required.

## Authentication
- **Base URL:** `https://YOUR-SITE/api/v1`
- **Header:** `Authorization: Bearer sk_...` (create a key under Developers → API Keys)
- Test auth with `GET /me`.

## Action: Create a short link
- **Method:** POST
- **URL:** `https://YOUR-SITE/api/v1/links`
- **Headers:** `Authorization: Bearer sk_...`, `Content-Type: application/json`
- **Body (JSON):**
  ```json
  { "destination": "{{long_url_from_previous_step}}", "title": "{{optional}}" }
  ```
- **Response:** `data.short_url` is the short link — map it into your next step.

## Trigger: New click (via webhook)
1. In Zapier create a *Catch Hook* trigger and copy its URL.
2. In Shortl → Developers → Webhooks, add that URL and select the
   `click.created` event.
3. Every click now fires your Zap. Payloads are signed with HMAC-SHA256 in the
   `X-Signature` header (secret shown on the webhook) if you want to verify.

## Trigger: New link (polling)
Use a *Schedule* + `GET /api/v1/links?per_page=25` and de-dupe on `data[].id`.

See `sample-payloads.json` for example request/response bodies.
