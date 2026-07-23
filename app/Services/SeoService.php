<?php

namespace App\Services;

/**
 * Stateless SEO helper: builds meta tag arrays and schema.org JSON-LD nodes.
 * Pure PHP — no DB writes.
 */
class SeoService
{
    /**
     * Normalize/merge SEO meta with site defaults.
     *
     * Accepted keys: title, description, canonical, image, type, noindex.
     */
    public static function meta(array $opts = []): array
    {
        $title = trim((string) ($opts['title'] ?? '')) ?: site_name();

        $description = trim((string) ($opts['description'] ?? ''));
        if ($description === '') {
            $description = (string) setting('site_description', setting('tagline', ''));
        }

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $opts['canonical'] ?? null,
            'image' => $opts['image'] ?? (setting('site_logo') ? storage_url(setting('site_logo')) : null),
            'type' => $opts['type'] ?? 'website',
            'noindex' => (bool) ($opts['noindex'] ?? false),
        ];
    }

    /**
     * Wrap a schema.org data array in a ready-to-print JSON-LD script tag.
     */
    public static function jsonLd(array $data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return '<script type="application/ld+json">' . $json . '</script>';
    }

    /**
     * schema.org Organization node for the site.
     */
    public static function organization(): array
    {
        $node = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => site_name(),
            'url' => url('/'),
        ];

        if (setting('site_logo')) {
            $node['logo'] = storage_url(setting('site_logo'));
        }

        return $node;
    }

    /**
     * schema.org BreadcrumbList from [['name' => .., 'url' => ..], ...].
     */
    public static function breadcrumb(array $items): array
    {
        $elements = [];
        $position = 1;

        foreach ($items as $item) {
            $element = [
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $item['name'] ?? '',
            ];
            if (! empty($item['url'])) {
                $element['item'] = $item['url'];
            }
            $elements[] = $element;
            $position++;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $elements,
        ];
    }
}
