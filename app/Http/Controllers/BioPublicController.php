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
