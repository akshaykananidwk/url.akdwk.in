<?php

namespace ShortlSdk;

/**
 * Minimal, dependency-free PHP client for the URL shortener REST API (v1).
 *
 * Authentication uses an API key sent as a Bearer token, exactly as the
 * server's `apikey` middleware expects:
 *
 *     Authorization: Bearer sk_xxxxxxxx
 *
 * Endpoints targeted (see routes/api.php, prefix "v1"):
 *   POST   /api/v1/links               shorten()
 *   GET    /api/v1/links               list()
 *   GET    /api/v1/links/{link}        get()
 *   DELETE /api/v1/links/{link}        delete()
 *   GET    /api/v1/links/{link}/stats  stats()
 *
 * Example:
 *   $client = new \ShortlSdk\Client('sk_live_xxx', 'https://example.com');
 *   $link   = $client->shorten('https://laravel.com');
 *   echo $link['short_url'];
 */
class Client
{
    private string $apiKey;
    private string $baseUrl;
    private int $timeout;

    /**
     * @param string $apiKey  Your API key (starts with "sk_").
     * @param string $baseUrl Site root, e.g. "https://example.com". The
     *                        "/api/v1" prefix is appended automatically. A
     *                        base URL that already ends in "/api/v1" is
     *                        accepted as-is.
     */
    public function __construct(string $apiKey, string $baseUrl, int $timeout = 30)
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = $this->normalizeBase($baseUrl);
        $this->timeout = $timeout;
    }

    /**
     * Create (shorten) a link.
     *
     * @param string $url  Destination URL (sent as the required "destination" field).
     * @param array  $opts Any of: alias, title, domain_id, space_id, password,
     *                     expires_at, max_clicks, utm, targeting.
     * @return array The created link resource.
     */
    public function shorten(string $url, array $opts = []): array
    {
        $payload = array_merge(['destination' => $url], $opts);

        return $this->request('POST', '/links', null, $payload)['data'];
    }

    /**
     * Fetch a single link by its identifier.
     *
     * @param string $alias Link identifier (the numeric id returned by the API).
     */
    public function get(string $alias): array
    {
        return $this->request('GET', '/links/' . rawurlencode($alias))['data'];
    }

    /**
     * List links.
     *
     * @param array $params Optional query params: q, space_id, per_page.
     * @return array The full response, including "data" and "meta".
     */
    public function list(array $params = []): array
    {
        return $this->request('GET', '/links', $params);
    }

    /**
     * Analytics for a link (totals, series and breakdowns).
     *
     * @param string $alias Link identifier.
     */
    public function stats(string $alias): array
    {
        return $this->request('GET', '/links/' . rawurlencode($alias) . '/stats')['data'];
    }

    /**
     * Delete a link.
     *
     * @return bool True on success.
     */
    public function delete(string $alias): bool
    {
        $this->request('DELETE', '/links/' . rawurlencode($alias));

        return true;
    }

    /* ------------------------------------------------------------------ */

    /**
     * @throws ApiException on any non-2xx response or transport error.
     */
    private function request(string $method, string $path, ?array $query = null, ?array $body = null): array
    {
        $url = $this->baseUrl . $path;
        if (! empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Accept: application/json',
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);
        if ($response === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new ApiException('Request failed: ' . $err, 0);
        }

        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = $response === '' ? [] : json_decode($response, true);
        if (! is_array($decoded)) {
            $decoded = [];
        }

        if ($status < 200 || $status >= 300) {
            $message = $decoded['message'] ?? ('HTTP ' . $status);
            throw new ApiException($message, $status, $decoded);
        }

        return $decoded;
    }

    private function normalizeBase(string $baseUrl): string
    {
        $baseUrl = rtrim($baseUrl, '/');
        if (! preg_match('#/api/v1$#', $baseUrl)) {
            $baseUrl .= '/api/v1';
        }

        return $baseUrl;
    }
}
