// Zero-dependency JS/Node client for the URL shortener REST API (v1).
// Works in Node 18+ and modern browsers (uses the global `fetch`).
//
// Authentication uses an API key sent as a Bearer token, exactly as the
// server's `apikey` middleware expects:
//
//     Authorization: Bearer sk_xxxxxxxx
//
// Endpoints targeted (see routes/api.php, prefix "v1"):
//   POST   /api/v1/links               shorten()
//   GET    /api/v1/links               list()
//   GET    /api/v1/links/{link}        get()
//   DELETE /api/v1/links/{link}        remove()
//   GET    /api/v1/links/{link}/stats  stats()

/**
 * Error thrown for any non-2xx API response.
 * @property {number} status HTTP status code
 * @property {object} body   Decoded JSON error body (may be empty)
 */
export class ShortlError extends Error {
  constructor(message, status, body) {
    super(message);
    this.name = 'ShortlError';
    this.status = status;
    this.body = body || {};
  }
}

export class ShortlClient {
  /**
   * @param {string} apiKey  Your API key (starts with "sk_").
   * @param {string} baseUrl Site root, e.g. "https://example.com". The
   *                         "/api/v1" prefix is appended automatically.
   */
  constructor(apiKey, baseUrl) {
    if (!apiKey) throw new Error('An API key is required.');
    if (!baseUrl) throw new Error('A base URL is required.');
    this.apiKey = apiKey;
    this.baseUrl = normalizeBase(baseUrl);
  }

  /**
   * Create (shorten) a link.
   * @param {string} url  Destination URL (sent as the "destination" field).
   * @param {object} [opts] alias, title, domain_id, space_id, password,
   *                        expires_at, max_clicks, utm, targeting.
   * @returns {Promise<object>} The created link resource.
   */
  async shorten(url, opts = {}) {
    const res = await this.#request('POST', '/links', null, { destination: url, ...opts });
    return res.data;
  }

  /**
   * Fetch a single link by its identifier (numeric id).
   * @param {string|number} alias
   * @returns {Promise<object>}
   */
  async get(alias) {
    const res = await this.#request('GET', `/links/${encodeURIComponent(alias)}`);
    return res.data;
  }

  /**
   * List links.
   * @param {object} [params] Query params: q, space_id, per_page.
   * @returns {Promise<object>} Full response, including `data` and `meta`.
   */
  async list(params = {}) {
    return this.#request('GET', '/links', params);
  }

  /**
   * Analytics for a link (totals, series and breakdowns).
   * @param {string|number} alias
   * @returns {Promise<object>}
   */
  async stats(alias) {
    const res = await this.#request('GET', `/links/${encodeURIComponent(alias)}/stats`);
    return res.data;
  }

  /**
   * Delete a link.
   * @param {string|number} alias
   * @returns {Promise<boolean>} true on success.
   */
  async remove(alias) {
    await this.#request('DELETE', `/links/${encodeURIComponent(alias)}`);
    return true;
  }

  async #request(method, path, query, body) {
    let url = this.baseUrl + path;
    if (query && Object.keys(query).length) {
      const qs = new URLSearchParams();
      for (const [k, v] of Object.entries(query)) {
        if (v !== undefined && v !== null) qs.append(k, v);
      }
      const s = qs.toString();
      if (s) url += `?${s}`;
    }

    const headers = {
      Authorization: `Bearer ${this.apiKey}`,
      Accept: 'application/json',
    };
    const init = { method, headers };
    if (body !== undefined && body !== null) {
      headers['Content-Type'] = 'application/json';
      init.body = JSON.stringify(body);
    }

    const response = await fetch(url, init);

    let data = {};
    const text = await response.text();
    if (text) {
      try {
        data = JSON.parse(text);
      } catch {
        data = {};
      }
    }

    if (!response.ok) {
      const message = data.message || `HTTP ${response.status}`;
      throw new ShortlError(message, response.status, data);
    }

    return data;
  }
}

function normalizeBase(baseUrl) {
  let b = baseUrl.replace(/\/+$/, '');
  if (!/\/api\/v1$/.test(b)) b += '/api/v1';
  return b;
}

export default ShortlClient;
