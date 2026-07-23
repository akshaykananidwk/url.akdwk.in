"""Zero-dependency Python client for the URL shortener REST API (v1).

Uses only the standard library (``urllib``) — no ``requests`` dependency.

Authentication uses an API key sent as a Bearer token, exactly as the
server's ``apikey`` middleware expects::

    Authorization: Bearer sk_xxxxxxxx

Endpoints targeted (see routes/api.php, prefix "v1")::

    POST   /api/v1/links               shorten()
    GET    /api/v1/links               list_links()
    GET    /api/v1/links/{link}        get()
    DELETE /api/v1/links/{link}        delete()
    GET    /api/v1/links/{link}/stats  stats()

Example::

    from shortl_sdk import Client

    client = Client("sk_live_xxx", "https://example.com")
    link = client.shorten("https://laravel.com", alias="laravel")
    print(link["short_url"])
"""

import json
import re
import urllib.error
import urllib.parse
import urllib.request

__all__ = ["Client", "ShortlError"]


class ShortlError(Exception):
    """Raised for any non-2xx API response or transport error.

    Attributes:
        status: HTTP status code (0 for transport errors).
        body: Decoded JSON error body (may be an empty dict).
    """

    def __init__(self, message, status=0, body=None):
        super().__init__(message)
        self.status = status
        self.body = body or {}


class Client:
    """Client for the URL shortener REST API (v1)."""

    def __init__(self, api_key, base_url, timeout=30):
        """Create a client.

        Args:
            api_key: Your API key (starts with ``sk_``).
            base_url: Site root, e.g. ``https://example.com``. The ``/api/v1``
                prefix is appended automatically.
            timeout: Per-request timeout in seconds.
        """
        if not api_key:
            raise ValueError("An API key is required.")
        if not base_url:
            raise ValueError("A base URL is required.")
        self.api_key = api_key
        self.base_url = self._normalize_base(base_url)
        self.timeout = timeout

    # ------------------------------------------------------------------ API

    def shorten(self, url, **opts):
        """Create (shorten) a link.

        Args:
            url: Destination URL (sent as the required ``destination`` field).
            **opts: Any of alias, title, domain_id, space_id, password,
                expires_at, max_clicks, utm, targeting.

        Returns:
            The created link resource (dict).
        """
        payload = {"destination": url}
        payload.update(opts)
        return self._request("POST", "/links", body=payload)["data"]

    def get(self, alias):
        """Fetch a single link by its identifier (numeric id)."""
        return self._request("GET", "/links/%s" % urllib.parse.quote(str(alias)))["data"]

    def list_links(self, **params):
        """List links.

        Args:
            **params: Optional query params: q, space_id, per_page.

        Returns:
            The full response dict, including ``data`` and ``meta``.
        """
        return self._request("GET", "/links", query=params)

    def stats(self, alias):
        """Analytics for a link (totals, series and breakdowns)."""
        path = "/links/%s/stats" % urllib.parse.quote(str(alias))
        return self._request("GET", path)["data"]

    def delete(self, alias):
        """Delete a link. Returns ``True`` on success."""
        self._request("DELETE", "/links/%s" % urllib.parse.quote(str(alias)))
        return True

    # -------------------------------------------------------------- internal

    def _request(self, method, path, query=None, body=None):
        url = self.base_url + path
        if query:
            filtered = {k: v for k, v in query.items() if v is not None}
            if filtered:
                url += "?" + urllib.parse.urlencode(filtered)

        headers = {
            "Authorization": "Bearer %s" % self.api_key,
            "Accept": "application/json",
        }
        data = None
        if body is not None:
            data = json.dumps(body).encode("utf-8")
            headers["Content-Type"] = "application/json"

        request = urllib.request.Request(url, data=data, headers=headers, method=method)

        try:
            with urllib.request.urlopen(request, timeout=self.timeout) as response:
                raw = response.read().decode("utf-8")
                return self._decode(raw)
        except urllib.error.HTTPError as exc:
            raw = exc.read().decode("utf-8", errors="replace")
            decoded = self._decode(raw, quiet=True)
            message = decoded.get("message") or ("HTTP %s" % exc.code)
            raise ShortlError(message, exc.code, decoded)
        except urllib.error.URLError as exc:
            raise ShortlError("Request failed: %s" % exc.reason, 0)

    @staticmethod
    def _decode(raw, quiet=False):
        if not raw:
            return {}
        try:
            parsed = json.loads(raw)
        except ValueError:
            if quiet:
                return {}
            return {}
        return parsed if isinstance(parsed, dict) else {}

    @staticmethod
    def _normalize_base(base_url):
        base = base_url.rstrip("/")
        if not re.search(r"/api/v1$", base):
            base += "/api/v1"
        return base
