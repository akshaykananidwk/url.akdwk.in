<?php

namespace App\Http\Controllers;

use App\Models\Conversion;
use App\Models\Link;
use Illuminate\Http\Request;

/**
 * Conversion tracking. After a visitor clicks a short link, the merchant drops
 * a tracking pixel (or JS beacon) on their thank-you / success page:
 *
 *   <img src="https://short.example/cv/{alias}.gif" width="1" height="1" alt="">
 *   — or —
 *   <script src="https://short.example/cv/{alias}.js"></script>
 *
 * The redirect sets a short-lived cookie so only visitors who actually came
 * through the link are counted, and each visitor is counted once per link.
 */
class ConversionController extends Controller
{
    /** 1x1 GIF pixel that records a conversion. */
    public function pixel(Request $request, string $alias)
    {
        $this->record($request, $alias);

        // transparent 1x1 gif
        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($gif, 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }

    /** JS beacon variant (also lets sites pass a revenue value: ?value=9.99). */
    public function beacon(Request $request, string $alias)
    {
        $this->record($request, $alias);

        return response('/* ok */', 200, ['Content-Type' => 'application/javascript', 'Cache-Control' => 'no-store']);
    }

    protected function record(Request $request, string $alias): void
    {
        $link = Link::where('alias', $alias)->first();
        if (! $link) {
            return;
        }

        // Only count visitors who arrived through this link (cookie set at redirect),
        // and only once per visitor per link.
        $cookie = 'clk_' . $link->id;
        if (! $request->cookie($cookie)) {
            return;
        }
        $doneCookie = 'cv_' . $link->id;
        if ($request->cookie($doneCookie)) {
            return;
        }

        $ipHash = $request->ip() ? hash('sha256', $request->ip() . config('app.key')) : null;

        Conversion::create([
            'link_id' => $link->id,
            'user_id' => $link->user_id,
            'value' => is_numeric($request->query('value')) ? number_format((float) $request->query('value'), 2, '.', '') : null,
            'label' => $request->query('label') ? mb_substr((string) $request->query('label'), 0, 190) : $link->conversion_goal,
            'ip_hash' => $ipHash,
            'created_at' => now(),
        ]);
        Link::withoutEvents(fn () => Link::where('id', $link->id)->increment('conversions_count'));

        cookie()->queue($doneCookie, '1', 60 * 24 * 30);
        hook_action('conversion_recorded', $link);
    }
}
