<?php

namespace App\Http\Controllers\Site;

use App\Models\Link;
use App\Services\QrService;
use App\Services\SeoService;

class PreviewController extends \App\Http\Controllers\Controller
{
    /**
     * Public, indexable safety/preview page for a short link.
     */
    public function show(string $alias)
    {
        $link = Link::where('alias', $alias)->where('disabled', false)->first();

        if (! $link) {
            abort(404);
        }

        $shortUrl = $link->shortUrl();
        $host = parse_url($link->destination, PHP_URL_HOST) ?: $link->destination;
        $title = $link->title ?: $shortUrl;

        // QR of the short link (best-effort; skip gracefully if it fails).
        $qrSvg = null;
        try {
            $qrSvg = app(QrService::class)->svg($shortUrl, ['size' => 220]);
        } catch (\Throwable) {
            $qrSvg = null;
        }

        $canonical = route('preview.show', $link->alias);

        $seo = SeoService::meta([
            'title' => $title . ' — ' . __('Link preview') . ' — ' . site_name(),
            'description' => __('Preview and safety check for the short link :url before you continue to :host.', [
                'url' => $shortUrl,
                'host' => $host,
            ]),
            'canonical' => $canonical,
            'image' => route('og.link', $link->alias),
            'type' => 'website',
        ]);

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $title,
            'url' => $canonical,
            'description' => $seo['description'],
        ];

        return view('site.preview', [
            'link' => $link,
            'shortUrl' => $shortUrl,
            'host' => $host,
            'title' => $title,
            'qrSvg' => $qrSvg,
            'seo' => $seo,
            'jsonLd' => $jsonLd,
            // Individual vars consumed by partials/seo-meta.blade.php (layout include).
            'seoTitle' => $seo['title'],
            'seoDescription' => $seo['description'],
            'seoCanonical' => $seo['canonical'],
            'seoImage' => $seo['image'],
            'seoType' => $seo['type'],
            'seoNoindex' => $seo['noindex'],
            'seoJsonLd' => $jsonLd,
        ]);
    }
}
