<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\LinkService;
use App\Services\PlanLimits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Extra generators: file-to-link, vCard link, WhatsApp click-to-chat.
 * Each produces a normal short link whose redirect serves the special payload
 * (type column on links: file | vcard | whatsapp).
 */
class ToolsController extends Controller
{
    public function __construct(
        protected LinkService $links,
        protected PlanLimits $limits,
    ) {
    }

    public function index()
    {
        return view('user.tools.index');
    }

    public function fileToLink(Request $request)
    {
        $user = $request->user();
        abort_unless($this->limits->hasFeature($user, 'file_links'), 403, __('File links are not available on your plan.'));
        $this->links->guardQuota($user);

        $request->validate([
            'file' => 'required|file|max:20480|mimes:pdf,png,jpg,jpeg,gif,webp,svg,zip,txt,csv,docx,xlsx,pptx,mp3,mp4',
        ]);

        $path = $request->file('file')->store('files/' . $user->id, setting('storage_disk', 'public'));

        $link = $this->links->create($user, [
            'destination' => storage_url($path),
            'title' => $request->file('file')->getClientOriginalName(),
        ]);
        $link->update([
            'type' => 'file',
            'meta' => ['path' => $path, 'original_name' => $request->file('file')->getClientOriginalName(), 'size' => $request->file('file')->getSize()],
        ]);

        return back()->with('status', __('File uploaded.'))->with('created_link', $link->shortUrl());
    }

    public function vcard(Request $request)
    {
        $user = $request->user();
        $this->links->guardQuota($user);

        $data = $request->validate([
            'first_name' => 'required|string|max:60',
            'last_name' => 'nullable|string|max:60',
            'organization' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:190',
            'website' => 'nullable|url|max:500',
            'address' => 'nullable|string|max:300',
        ]);

        $link = $this->links->create($user, [
            'destination' => rtrim(config('app.url'), '/'),
            'title' => trim($data['first_name'] . ' ' . ($data['last_name'] ?? '')) . ' vCard',
        ]);
        $link->update(['type' => 'vcard', 'meta' => $data]);

        return back()->with('status', __('vCard link created.'))->with('created_link', $link->shortUrl());
    }

    public function whatsapp(Request $request)
    {
        $user = $request->user();
        $this->links->guardQuota($user);

        $data = $request->validate([
            'phone' => 'required|string|max:20|regex:/^[0-9+\s\-]+$/',
            'message' => 'nullable|string|max:500',
        ]);

        $phone = preg_replace('/[^0-9]/', '', $data['phone']);
        $wa = 'https://wa.me/' . $phone . ($data['message'] ?? null ? '?text=' . rawurlencode($data['message']) : '');

        $link = $this->links->create($user, ['destination' => $wa, 'title' => 'WhatsApp ' . $phone]);
        $link->update(['type' => 'whatsapp', 'meta' => $data]);

        return back()->with('status', __('WhatsApp link created.'))->with('created_link', $link->shortUrl());
    }
}
