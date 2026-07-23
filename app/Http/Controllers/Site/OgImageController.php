<?php

namespace App\Http\Controllers\Site;

use App\Models\BioPage;
use App\Models\Link;

class OgImageController extends \App\Http\Controllers\Controller
{
    /**
     * Dynamic Open Graph share image (SVG, 1200x630) for a short link.
     */
    public function link(string $alias)
    {
        $link = Link::where('alias', $alias)->where('disabled', false)->first();

        if (! $link) {
            abort(404);
        }

        $title = $link->title ?: $link->shortUrl();
        $lines = $this->wrap($title, 26, 2);
        $clicks = format_number($link->clicks_count ?? 0);

        $titleSvg = '';
        $y = count($lines) === 1 ? 340 : 300;
        foreach ($lines as $line) {
            $titleSvg .= '<text x="90" y="' . $y . '" font-family="Segoe UI, Helvetica, Arial, sans-serif" '
                . 'font-size="72" font-weight="700" fill="#ffffff">' . $this->esc($line) . '</text>';
            $y += 88;
        }

        $svg = $this->frame(
            eyebrow: $this->esc(site_name()),
            body: $titleSvg,
            footLabel: $this->esc(__('Clicks')),
            footValue: $this->esc($clicks),
            footUrl: $this->esc($link->shortUrl())
        );

        return $this->respond($svg);
    }

    /**
     * Dynamic Open Graph share image (SVG, 1200x630) for a bio page.
     */
    public function bio(string $username)
    {
        $page = BioPage::where('username', $username)->where('active', true)->first();

        if (! $page) {
            abort(404);
        }

        $title = $page->title ?: ('@' . $page->username);
        $lines = $this->wrap($title, 26, 2);
        $bio = $this->truncate((string) ($page->bio ?? ''), 90);
        $views = format_number($page->views ?? 0);

        $titleSvg = '';
        $y = 280;
        foreach ($lines as $line) {
            $titleSvg .= '<text x="90" y="' . $y . '" font-family="Segoe UI, Helvetica, Arial, sans-serif" '
                . 'font-size="72" font-weight="700" fill="#ffffff">' . $this->esc($line) . '</text>';
            $y += 88;
        }
        if ($bio !== '') {
            $titleSvg .= '<text x="90" y="' . ($y + 8) . '" font-family="Segoe UI, Helvetica, Arial, sans-serif" '
                . 'font-size="34" font-weight="400" fill="#e0e7ff">' . $this->esc($bio) . '</text>';
        }

        $svg = $this->frame(
            eyebrow: '@' . $this->esc($page->username),
            body: $titleSvg,
            footLabel: $this->esc(__('Views')),
            footValue: $this->esc($views),
            footUrl: $this->esc(site_name())
        );

        return $this->respond($svg);
    }

    /* ------------------------------------------------------------------ */

    /** Build the branded 1200x630 SVG frame around pre-rendered body markup. */
    protected function frame(string $eyebrow, string $body, string $footLabel, string $footValue, string $footUrl): string
    {
        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630">
    <defs>
        <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#6366f1"/>
            <stop offset="1" stop-color="#4338ca"/>
        </linearGradient>
    </defs>
    <rect width="1200" height="630" fill="url(#bg)"/>
    <rect x="0" y="0" width="12" height="630" fill="#ffffff" opacity="0.85"/>
    <text x="90" y="150" font-family="Segoe UI, Helvetica, Arial, sans-serif" font-size="34" font-weight="600" fill="#c7d2fe" letter-spacing="2">{$eyebrow}</text>
    {$body}
    <line x1="90" y1="500" x2="1110" y2="500" stroke="#ffffff" stroke-opacity="0.25" stroke-width="2"/>
    <text x="90" y="560" font-family="Segoe UI, Helvetica, Arial, sans-serif" font-size="30" font-weight="400" fill="#e0e7ff">{$footUrl}</text>
    <text x="1110" y="552" text-anchor="end" font-family="Segoe UI, Helvetica, Arial, sans-serif" font-size="26" font-weight="400" fill="#c7d2fe">{$footLabel}</text>
    <text x="1110" y="590" text-anchor="end" font-family="Segoe UI, Helvetica, Arial, sans-serif" font-size="44" font-weight="700" fill="#ffffff">{$footValue}</text>
</svg>
SVG;
    }

    protected function respond(string $svg)
    {
        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /** XML-safe escaping. */
    protected function esc(string $text): string
    {
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /** Simple word-wrap into at most $maxLines lines of ~$perLine chars. */
    protected function wrap(string $text, int $perLine, int $maxLines): array
    {
        $text = trim(preg_replace('/\s+/', ' ', $text));
        if ($text === '') {
            return [''];
        }

        $words = explode(' ', $text);
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current . ' ' . $word;
            if (mb_strlen($candidate) <= $perLine || $current === '') {
                $current = $candidate;
            } else {
                $lines[] = $current;
                $current = $word;
                if (count($lines) === $maxLines) {
                    break;
                }
            }
        }

        if (count($lines) < $maxLines && $current !== '') {
            $lines[] = $current;
        }

        $lines = array_slice($lines, 0, $maxLines);

        // If we truncated content, add an ellipsis to the last line.
        $rendered = implode(' ', $lines);
        if (mb_strlen($rendered) < mb_strlen($text)) {
            $last = array_pop($lines);
            $last = mb_substr($last, 0, max(0, $perLine - 1));
            $lines[] = rtrim($last) . '…';
        }

        return $lines ?: [''];
    }

    protected function truncate(string $text, int $max): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text));
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $max - 1)) . '…';
    }
}
