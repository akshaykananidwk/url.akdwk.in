@extends('layouts.app')

@section('title', __('API documentation') . ' — ' . site_name())
@section('page-title', __('API documentation'))

@php
    $base = url('/api/v1');
    $methodBadge = ['GET' => 'badge-green', 'POST' => 'badge-brand', 'PUT' => 'badge-amber', 'DELETE' => 'badge-red'];

    $linkJson = <<<JSON
{
  "id": 42,
  "alias": "spring-sale",
  "short_url": "https://sho.rt/spring-sale",
  "destination": "https://example.com/landing",
  "title": "Spring sale",
  "space_id": null,
  "domain_id": null,
  "disabled": false,
  "expires_at": null,
  "max_clicks": null,
  "clicks": 1289,
  "unique_clicks": 1054,
  "qr_scans": 87,
  "created_at": "2026-05-01T09:30:00+00:00"
}
JSON;

    $endpoints = [
        [
            'method' => 'GET', 'path' => '/me', 'title' => __('Account overview'),
            'desc' => __('Returns the authenticated account with its plan and totals.'),
            'params' => [],
            'curl' => "curl {$base}/me \\\n  -H \"Authorization: Bearer sk_YOUR_KEY\"",
            'response' => "{\n  \"data\": {\n    \"name\": \"Jane Doe\",\n    \"email\": \"jane@example.com\",\n    \"plan\": \"Pro\",\n    \"links\": 120,\n    \"clicks\": 45210\n  }\n}",
        ],
        [
            'method' => 'GET', 'path' => '/links', 'title' => __('List links'),
            'desc' => __('Paginated list of your links, newest first.'),
            'params' => [
                ['q', __('string, optional'), __('Search in alias, destination, title and notes.')],
                ['space_id', __('integer, optional'), __('Only links inside this space.')],
                ['per_page', __('integer, optional'), __('Items per page, 1–100. Default 25.')],
                ['page', __('integer, optional'), __('Page number.')],
            ],
            'curl' => "curl \"{$base}/links?q=sale&per_page=25\" \\\n  -H \"Authorization: Bearer sk_YOUR_KEY\"",
            'response' => "{\n  \"data\": [ " . '/* link objects */' . " ],\n  \"meta\": { \"total\": 120, \"page\": 1, \"last_page\": 5 }\n}",
        ],
        [
            'method' => 'POST', 'path' => '/links', 'title' => __('Create a link'),
            'desc' => __('Creates a short link. Returns 201 with the new link, or 422 with validation errors.'),
            'params' => [
                ['destination', __('string, required'), __('The URL to shorten.')],
                ['alias', __('string, optional'), __('Custom alias. Auto-generated when omitted.')],
                ['title', __('string, optional'), __('Internal title.')],
                ['domain_id', __('integer, optional'), __('Branded domain id (see GET /domains).')],
                ['space_id', __('integer, optional'), __('Space id (see GET /spaces).')],
                ['password', __('string, optional'), __('Password-protect the link.')],
                ['expires_at', __('datetime, optional'), __('Expiration date, e.g. 2026-12-31 23:59.')],
                ['max_clicks', __('integer, optional'), __('Expire after this many clicks.')],
                ['utm', __('object, optional'), __('UTM parameters: source, medium, campaign, term, content.')],
                ['targeting', __('object, optional'), __('Targeting rules: country, platform, language, device, time, rotation.')],
            ],
            'curl' => "curl -X POST {$base}/links \\\n  -H \"Authorization: Bearer sk_YOUR_KEY\" \\\n  -H \"Content-Type: application/json\" \\\n  -d '{\"destination\": \"https://example.com/landing\", \"alias\": \"spring-sale\"}'",
            'response' => "{\n  \"data\": " . $linkJson . "\n}",
        ],
        [
            'method' => 'GET', 'path' => '/links/{id}', 'title' => __('Get a link'),
            'desc' => __('Returns a single link you own. 404 when the id does not exist or belongs to another account.'),
            'params' => [['id', __('integer, path'), __('The link id.')]],
            'curl' => "curl {$base}/links/42 \\\n  -H \"Authorization: Bearer sk_YOUR_KEY\"",
            'response' => "{\n  \"data\": " . $linkJson . "\n}",
        ],
        [
            'method' => 'PUT', 'path' => '/links/{id}', 'title' => __('Update a link'),
            'desc' => __('Partially updates a link — send only the fields you want to change.'),
            'params' => [
                ['destination', __('string, optional'), __('New destination URL.')],
                ['alias', __('string, optional'), __('New alias.')],
                ['title', __('string, optional'), __('New title.')],
                ['space_id', __('integer, optional'), __('Move to a space (null to clear).')],
                ['disabled', __('boolean, optional'), __('Enable/disable the link.')],
                ['expires_at', __('datetime, optional'), __('Expiration date.')],
                ['max_clicks', __('integer, optional'), __('Click limit.')],
                ['utm', __('object, optional'), __('UTM parameters.')],
                ['targeting', __('object, optional'), __('Targeting rules.')],
            ],
            'curl' => "curl -X PUT {$base}/links/42 \\\n  -H \"Authorization: Bearer sk_YOUR_KEY\" \\\n  -H \"Content-Type: application/json\" \\\n  -d '{\"disabled\": true}'",
            'response' => "{\n  \"data\": " . '/* updated link object */' . "\n}",
        ],
        [
            'method' => 'DELETE', 'path' => '/links/{id}', 'title' => __('Delete a link'),
            'desc' => __('Permanently deletes a link and its statistics.'),
            'params' => [['id', __('integer, path'), __('The link id.')]],
            'curl' => "curl -X DELETE {$base}/links/42 \\\n  -H \"Authorization: Bearer sk_YOUR_KEY\"",
            'response' => "{\n  \"message\": \"Link deleted.\"\n}",
        ],
        [
            'method' => 'GET', 'path' => '/links/{id}/stats', 'title' => __('Link statistics'),
            'desc' => __('Totals, daily series and breakdowns for a date range (defaults to the last 30 days).'),
            'params' => [
                ['from', __('date, optional'), __('Start date, e.g. 2026-06-01.')],
                ['to', __('date, optional'), __('End date, e.g. 2026-06-30.')],
            ],
            'curl' => "curl \"{$base}/links/42/stats?from=2026-06-01&to=2026-06-30\" \\\n  -H \"Authorization: Bearer sk_YOUR_KEY\"",
            'response' => "{\n  \"data\": {\n    \"totals\": { \"clicks\": 1289, \"uniques\": 1054, \"qr_scans\": 87 },\n    \"series\": { \"2026-06-01\": { \"clicks\": 40, \"uniques\": 31, \"qr_scans\": 2 } },\n    \"breakdowns\": {\n      \"country\": [ { \"key\": \"US\", \"count\": 512 } ],\n      \"referer\": [], \"os\": [], \"browser\": [], \"device\": [], \"language\": []\n    }\n  }\n}",
        ],
        [
            'method' => 'GET', 'path' => '/links/{id}/qr', 'title' => __('QR code image'),
            'desc' => __('Returns a QR code image for the link (binary response, not JSON).'),
            'params' => [
                ['format', __('string, optional'), __('png (default) or svg.')],
                ['fg', __('string, optional'), __('Foreground color, default #000000.')],
                ['bg', __('string, optional'), __('Background color, default #ffffff.')],
                ['size', __('integer, optional'), __('Image size in pixels, default 400.')],
                ['ec_level', __('string, optional'), __('low, medium, quartile or high.')],
            ],
            'curl' => "curl \"{$base}/links/42/qr?format=png&size=600\" \\\n  -H \"Authorization: Bearer sk_YOUR_KEY\" \\\n  -o qr.png",
            'response' => __('Binary image data (image/png or image/svg+xml).'),
        ],
        [
            'method' => 'GET', 'path' => '/spaces', 'title' => __('List spaces'),
            'desc' => __('All spaces in your account.'),
            'params' => [],
            'curl' => "curl {$base}/spaces \\\n  -H \"Authorization: Bearer sk_YOUR_KEY\"",
            'response' => "{\n  \"data\": [\n    { \"id\": 1, \"name\": \"Marketing\", \"color\": \"#6366f1\", \"links\": 24 }\n  ]\n}",
        ],
        [
            'method' => 'GET', 'path' => '/domains', 'title' => __('List domains'),
            'desc' => __('Domains available for your links: global domains plus your verified custom domains.'),
            'params' => [],
            'curl' => "curl {$base}/domains \\\n  -H \"Authorization: Bearer sk_YOUR_KEY\"",
            'response' => "{\n  \"data\": [\n    { \"id\": 3, \"domain\": \"go.yourbrand.com\", \"global\": false, \"ssl\": true }\n  ]\n}",
        ],
    ];
@endphp

@section('content')
<div class="max-w-4xl mx-auto space-y-5">

    {{-- Intro --}}
    <div class="card card-pad space-y-3">
        <h2 class="font-semibold text-lg">{{ __('REST API v1') }}</h2>
        <p class="text-sm text-slate-600 dark:text-slate-300">
            {{ __('All endpoints live under the base URL below, accept and return JSON, and are authenticated with an API key from the Developers page.') }}
        </p>
        <div>
            <p class="label">{{ __('Base URL') }}</p>
            <div class="flex items-center gap-2">
                <code class="font-mono text-sm rounded-lg bg-slate-100 dark:bg-slate-800 px-3 py-2 flex-1 overflow-x-auto">{{ $base }}</code>
                <button type="button" class="btn-secondary btn-sm shrink-0" onclick="copyText(@js($base))"><x-icon name="copy" class="h-4 w-4"/></button>
            </div>
        </div>
        <div>
            <p class="label">{{ __('Authentication') }}</p>
            <p class="text-sm text-slate-600 dark:text-slate-300 mb-2">
                {{ __('Send your key (starting with sk_) in the Authorization header on every request:') }}
            </p>
            <pre class="overflow-x-auto rounded-xl bg-slate-900 text-slate-100 text-xs p-4">Authorization: Bearer sk_YOUR_KEY</pre>
        </div>
    </div>

    {{-- Rate limits --}}
    <div class="card card-pad space-y-3">
        <h2 class="font-semibold">{{ __('Rate limits') }}</h2>
        <p class="text-sm text-slate-600 dark:text-slate-300">
            {{ __('Requests are limited per key and per minute according to your plan. Every response includes the current usage; exceeding the limit returns 429 with a Retry-After header.') }}
        </p>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="py-2 pe-4">{{ __('Header') }}</th><th class="py-2">{{ __('Meaning') }}</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <tr><td class="py-2 pe-4 font-mono text-xs">X-RateLimit-Limit</td><td class="py-2">{{ __('Allowed requests per minute for your plan.') }}</td></tr>
                    <tr><td class="py-2 pe-4 font-mono text-xs">X-RateLimit-Remaining</td><td class="py-2">{{ __('Requests left in the current minute.') }}</td></tr>
                    <tr><td class="py-2 pe-4 font-mono text-xs">Retry-After</td><td class="py-2">{{ __('Seconds to wait after a 429 response.') }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Endpoints --}}
    @foreach($endpoints as $endpoint)
        <div class="card card-pad space-y-4">
            <div class="flex flex-wrap items-center gap-2">
                <span class="{{ $methodBadge[$endpoint['method']] }} font-mono">{{ $endpoint['method'] }}</span>
                <code class="font-mono text-sm font-semibold">{{ $endpoint['path'] }}</code>
                <span class="text-sm text-slate-500">— {{ $endpoint['title'] }}</span>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-300">{{ $endpoint['desc'] }}</p>

            @if(count($endpoint['params']))
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="py-2 pe-4">{{ __('Parameter') }}</th>
                                <th class="py-2 pe-4">{{ __('Type') }}</th>
                                <th class="py-2">{{ __('Description') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($endpoint['params'] as [$param, $type, $desc])
                                <tr>
                                    <td class="py-2 pe-4 font-mono text-xs whitespace-nowrap">{{ $param }}</td>
                                    <td class="py-2 pe-4 text-xs text-slate-500 whitespace-nowrap">{{ $type }}</td>
                                    <td class="py-2">{{ $desc }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div>
                <p class="label">{{ __('Example request') }}</p>
                <pre class="overflow-x-auto rounded-xl bg-slate-900 text-slate-100 text-xs p-4">{{ $endpoint['curl'] }}</pre>
            </div>
            <div>
                <p class="label">{{ __('Example response') }}</p>
                <pre class="overflow-x-auto rounded-xl bg-slate-900 text-slate-100 text-xs p-4">{{ $endpoint['response'] }}</pre>
            </div>
        </div>
    @endforeach

    {{-- Postman --}}
    <div class="card card-pad">
        <h2 class="font-semibold mb-2">{{ __('Postman collection') }}</h2>
        <p class="text-sm text-slate-600 dark:text-slate-300">
            {{ __('A ready-made Postman collection ships with the application — download it from the docs folder of your installation (docs/postman_collection.json) and set your API key as the bearer token.') }}
        </p>
    </div>
</div>
@endsection
