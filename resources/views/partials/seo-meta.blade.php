@php
    $seoTitle = $seoTitle ?? null;
    $seoDescription = $seoDescription ?? null;
    $seoCanonical = $seoCanonical ?? null;
    $seoImage = $seoImage ?? null;
    $seoType = $seoType ?? null;
    $seoNoindex = $seoNoindex ?? false;
    $seoJsonLd = $seoJsonLd ?? null;

    $metaTitle = trim((string) $seoTitle) !== '' ? $seoTitle : site_name();
    $metaDescription = trim((string) $seoDescription) !== ''
        ? $seoDescription
        : (string) setting('site_description', setting('tagline', ''));
    $metaImage = $seoImage ?: (setting('site_logo') ? storage_url(setting('site_logo')) : null);
    $metaUrl = $seoCanonical ?: url()->current();
    $metaType = $seoType ?: 'website';
@endphp
<title>{{ $metaTitle }}</title>
@if($metaDescription !== '')
<meta name="description" content="{{ $metaDescription }}">
@endif
<link rel="canonical" href="{{ $metaUrl }}">
@if($seoNoindex)
<meta name="robots" content="noindex, nofollow">
@endif

<meta property="og:site_name" content="{{ site_name() }}">
<meta property="og:type" content="{{ $metaType }}">
<meta property="og:title" content="{{ $metaTitle }}">
@if($metaDescription !== '')
<meta property="og:description" content="{{ $metaDescription }}">
@endif
<meta property="og:url" content="{{ $metaUrl }}">
@if($metaImage)
<meta property="og:image" content="{{ $metaImage }}">
@endif

<meta name="twitter:card" content="{{ $metaImage ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $metaTitle }}">
@if($metaDescription !== '')
<meta name="twitter:description" content="{{ $metaDescription }}">
@endif
@if($metaImage)
<meta name="twitter:image" content="{{ $metaImage }}">
@endif

@if($seoJsonLd)
@php
    $jsonLdOut = is_array($seoJsonLd)
        ? \App\Services\SeoService::jsonLd($seoJsonLd)
        : (string) $seoJsonLd;
@endphp
{!! $jsonLdOut !!}
@endif
