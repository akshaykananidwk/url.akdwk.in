<?php

namespace App\Http\Controllers;

use App\Models\BioBlock;
use App\Models\BioPage;
use Illuminate\Http\Request;

class BioPublicController extends Controller
{
    public function show(Request $request, string $username)
    {
        $page = BioPage::with('blocks')->where('username', $username)->where('active', true)->firstOrFail();
        if ($page->user?->isSuspended()) {
            abort(404);
        }

        $page->increment('views');

        return view('bio.show', ['page' => $page, 'blocks' => $page->visibleBlocks()]);
    }

    /** Block-level click tracking, then redirect to the block target. */
    public function blockClick(Request $request, BioBlock $block)
    {
        $block->increment('clicks');
        $target = $block->content['url'] ?? null;

        return $target ? redirect()->away($target) : back();
    }

    /**
     * Tip jar: a supporter picks an amount, we record the intent and forward
     * them to the owner's configured payment target (UPI / PayPal.me / link).
     * The tip block content: {method: upi|paypal|url, target, currency}.
     */
    public function tip(Request $request, BioPage $bioPage)
    {
        $data = $request->validate([
            'block_id' => 'required|integer',
            'amount' => 'required|numeric|min:1|max:1000000',
            'name' => 'nullable|string|max:100',
            'message' => 'nullable|string|max:500',
        ]);

        $block = $bioPage->blocks()->where('id', $data['block_id'])->where('type', 'tip')->firstOrFail();
        $c = $block->content ?? [];
        $method = $c['method'] ?? 'url';
        $target = $c['target'] ?? '';
        $currency = $c['currency'] ?? setting('currency', 'USD');
        $amount = number_format((float) $data['amount'], 2, '.', '');

        \App\Models\Tip::create([
            'bio_page_id' => $bioPage->id,
            'user_id' => $bioPage->user_id,
            'supporter_name' => $data['name'] ?? null,
            'message' => $data['message'] ?? null,
            'amount' => $amount,
            'currency' => $currency,
            'gateway' => $method,
            'status' => 'pending',
        ]);
        $block->increment('clicks');

        $payUrl = match ($method) {
            'upi' => 'upi://pay?' . http_build_query([
                'pa' => $target, 'pn' => $bioPage->title, 'am' => $amount, 'cu' => 'INR',
                'tn' => 'Tip for ' . $bioPage->username,
            ]),
            'paypal' => 'https://www.paypal.com/paypalme/' . ltrim($target, '/') . '/' . $amount,
            default => $target, // custom URL (Ko-fi, BuyMeACoffee, Stripe link, …)
        };

        return redirect()->away($payUrl);
    }

    public function subscribe(Request $request, BioPage $bioPage)
    {
        $data = $request->validate(['email' => 'required|email|max:190', 'block_id' => 'nullable|integer']);

        $bioPage->subscribers()->firstOrCreate(
            ['email' => strtolower($data['email'])],
            ['bio_block_id' => $data['block_id'] ?? null]
        );

        return back()->with('bio_status', __('Thanks for subscribing!'));
    }

    /** vCard download block on a bio page. */
    public function vcard(BioPage $bioPage)
    {
        $block = $bioPage->blocks()->where('type', 'vcard')->firstOrFail();
        $m = $block->content;
        $lines = ['BEGIN:VCARD', 'VERSION:3.0',
            'FN:' . ($m['name'] ?? $bioPage->title),
        ];
        if (! empty($m['phone'])) { $lines[] = 'TEL;TYPE=CELL:' . $m['phone']; }
        if (! empty($m['email'])) { $lines[] = 'EMAIL:' . $m['email']; }
        if (! empty($m['organization'])) { $lines[] = 'ORG:' . $m['organization']; }
        $lines[] = 'URL:' . $bioPage->url();
        $lines[] = 'END:VCARD';

        return response(implode("\r\n", $lines), 200, [
            'Content-Type' => 'text/vcard; charset=utf-8',
            'Content-Disposition' => 'attachment; filename=' . $bioPage->username . '.vcf',
        ]);
    }
}
