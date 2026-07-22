<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    @php($og = $link->og ?? [])
    <title>{{ $og['title'] ?? ($link->title ?: $link->alias) }}</title>
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $link->shortUrl() }}">
    @if(!empty($og['title']))
        <meta property="og:title" content="{{ $og['title'] }}">
        <meta name="twitter:title" content="{{ $og['title'] }}">
    @endif
    @if(!empty($og['description']))
        <meta property="og:description" content="{{ $og['description'] }}">
        <meta name="twitter:description" content="{{ $og['description'] }}">
        <meta name="description" content="{{ $og['description'] }}">
    @endif
    @if(!empty($og['image']))
        <meta property="og:image" content="{{ str_starts_with($og['image'], 'http') ? $og['image'] : storage_url($og['image']) }}">
        <meta name="twitter:image" content="{{ str_starts_with($og['image'], 'http') ? $og['image'] : storage_url($og['image']) }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="robots" content="noindex">
    <meta http-equiv="refresh" content="0;url={{ $link->destinationWithUtm() }}">
</head>
<body>
    <p><a href="{{ $link->destinationWithUtm() }}">{{ $og['title'] ?? $link->shortUrl() }}</a></p>
</body>
</html>
