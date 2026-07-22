<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $link->title ?: $link->alias }}</title>
    <style>
        html, body { margin: 0; padding: 0; height: 100%; overflow: hidden; }
        iframe { display: block; width: 100vw; height: 100vh; border: 0; }
    </style>
</head>
<body>
    <iframe src="{{ $destination }}" title="{{ $link->title ?: $link->alias }}"
            allow="accelerometer; autoplay; encrypted-media; fullscreen; geolocation; gyroscope; payment"
            allowfullscreen></iframe>
</body>
</html>
