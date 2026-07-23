<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Link;
use Illuminate\Http\Response;

class WidgetController extends Controller
{
    /**
     * Self-contained JavaScript widget that injects a small stats card
     * plus a backlink to the site. Embeddable on any third-party page.
     */
    public function js(string $alias): Response
    {
        $link = Link::where('alias', $alias)
            ->where('disabled', false)
            ->where('public_stats', true)
            ->first();

        if (! $link) {
            abort(404);
        }

        // Everything the JS needs, JSON-encoded so it is XSS-safe by construction.
        $data = json_encode([
            'title' => $link->title ?: $link->shortUrl(),
            'shortUrl' => $link->shortUrl(),
            'clicks' => (int) $link->clicks_count,
            'clicksLabel' => __('clicks'),
            'siteName' => site_name(),
            'siteUrl' => rtrim(url('/'), '/') . '/?ref=widget',
            'ctaLabel' => __('Shorten yours'),
        ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

        $js = <<<JS
(function () {
  var d = {$data};
  var esc = function (s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  };
  var html = ''
    + '<div style="display:inline-block;max-width:320px;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;'
    + 'border:1px solid #e2e8f0;border-radius:12px;padding:16px;background:#fff;color:#0f172a;box-shadow:0 1px 3px rgba(0,0,0,.08);line-height:1.4">'
    + '<div style="font-weight:600;font-size:15px;margin-bottom:6px;word-break:break-word">' + esc(d.title) + '</div>'
    + '<a href="' + esc(d.shortUrl) + '" target="_blank" rel="noopener" style="font-size:13px;color:#6366f1;text-decoration:none;word-break:break-all">' + esc(d.shortUrl) + '</a>'
    + '<div style="margin-top:10px;font-size:22px;font-weight:700">' + esc(d.clicks.toLocaleString()) + ' <span style="font-size:12px;font-weight:500;color:#64748b">' + esc(d.clicksLabel) + '</span></div>'
    + '<a href="' + esc(d.siteUrl) + '" target="_blank" rel="noopener" style="display:inline-block;margin-top:12px;font-size:11px;color:#94a3b8;text-decoration:none">⚡ ' + esc(d.ctaLabel) + ' → ' + esc(d.siteName) + '</a>'
    + '</div>';
  if (document.currentScript && document.currentScript.parentNode) {
    var span = document.createElement('span');
    span.innerHTML = html;
    document.currentScript.parentNode.insertBefore(span, document.currentScript);
  } else {
    document.write(html);
  }
})();
JS;

        return response($js, 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * Minimal standalone HTML page designed to be dropped into an <iframe>.
     */
    public function embed(string $alias): Response
    {
        $link = Link::where('alias', $alias)
            ->where('disabled', false)
            ->where('public_stats', true)
            ->first();

        if (! $link) {
            abort(404);
        }

        $html = view('site.embed', [
            'link' => $link,
        ])->render();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Content-Security-Policy' => "frame-ancestors *",
        ]);
    }
}
