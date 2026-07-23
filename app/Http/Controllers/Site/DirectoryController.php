<?php

namespace App\Http\Controllers\Site;

use App\Models\BioPage;
use App\Services\SeoService;
use Illuminate\Http\Request;

class DirectoryController extends \App\Http\Controllers\Controller
{
    /**
     * Public, SEO-facing directory of active creator bio pages.
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $query = BioPage::query()->where('active', true);

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('username', 'like', "%{$q}%")
                    ->orWhere('bio', 'like', "%{$q}%");
            });
        }

        $pages = $query->orderByDesc('views')
            ->paginate(24)
            ->withQueryString();

        $seo = SeoService::meta([
            'title' => __('Creator Directory') . ' — ' . site_name(),
            'description' => __('Discover creators, brands and link-in-bio pages on :site.', ['site' => site_name()]),
            'canonical' => route('directory.index'),
            'type' => 'website',
        ]);

        $jsonLd = SeoService::breadcrumb([
            ['name' => site_name(), 'url' => url('/')],
            ['name' => __('Directory'), 'url' => route('directory.index')],
        ]);

        return view('site.directory', [
            'pages' => $pages,
            'q' => $q,
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

    /**
     * RSS 2.0 feed of the 50 most recent active bio pages.
     */
    public function feed()
    {
        $pages = BioPage::query()
            ->where('active', true)
            ->latest('created_at')
            ->limit(50)
            ->get();

        $site = htmlspecialchars(site_name(), ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $selfUrl = htmlspecialchars(route('directory.feed'), ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $dirUrl = htmlspecialchars(route('directory.index'), ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $desc = htmlspecialchars(
            __('Newest creators on :site', ['site' => site_name()]),
            ENT_XML1 | ENT_QUOTES,
            'UTF-8'
        );

        $items = '';
        foreach ($pages as $page) {
            $link = htmlspecialchars(route('bio.show', $page->username), ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $itemTitle = htmlspecialchars(
                $page->title ?: ('@' . $page->username),
                ENT_XML1 | ENT_QUOTES,
                'UTF-8'
            );
            $itemDesc = htmlspecialchars((string) ($page->bio ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $pubDate = optional($page->created_at)->toRssString() ?? now()->toRssString();

            $items .= <<<XML
        <item>
            <title>{$itemTitle}</title>
            <link>{$link}</link>
            <guid isPermaLink="true">{$link}</guid>
            <description>{$itemDesc}</description>
            <pubDate>{$pubDate}</pubDate>
        </item>

XML;
        }

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>{$site} — Directory</title>
        <link>{$dirUrl}</link>
        <atom:link href="{$selfUrl}" rel="self" type="application/rss+xml"/>
        <description>{$desc}</description>
        <language>en</language>
{$items}    </channel>
</rss>
XML;

        return response($xml, 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
        ]);
    }
}
