<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Link;
use App\Models\QrCode;
use App\Services\PlanLimits;
use App\Services\QrService;
use Illuminate\Http\Request;

class QrCodeController extends Controller
{
    public function __construct(
        protected QrService $qr,
        protected PlanLimits $limits,
    ) {
    }

    public function index(Request $request)
    {
        return view('user.qr.index', [
            'codes' => $request->user()->qrCodes()->with('link.domain')->orderByDesc('created_at')->paginate(12),
            'links' => $request->user()->links()->active()->orderByDesc('created_at')->limit(200)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($this->limits->hasFeature($user, 'qr'), 403, __('QR codes are not available on your plan.'));
        abort_unless($this->limits->canCreate($user, 'qr_codes'), 403, __('You have reached the QR code limit of your plan.'));

        $data = $request->validate([
            'name' => 'required|string|max:60',
            'link_id' => 'required|integer',
            'fg' => 'nullable|string|max:9',
            'bg' => 'nullable|string|max:9',
            'ec_level' => 'nullable|in:low,medium,quartile,high',
            'frame_text' => 'nullable|string|max:40',
            'logo' => 'nullable|image|max:1024',
        ]);

        $link = $user->links()->findOrFail($data['link_id']);

        $options = [
            'fg' => $data['fg'] ?? '#000000',
            'bg' => $data['bg'] ?? '#ffffff',
            'ec_level' => $data['ec_level'] ?? 'medium',
            'frame_text' => $data['frame_text'] ?? '',
        ];

        if ($request->hasFile('logo')) {
            abort_unless($this->limits->hasFeature($user, 'qr_logo'), 403, __('QR logos are not available on your plan.'));
            $options['logo'] = $request->file('logo')->store('qr-logos', setting('storage_disk', 'public'));
        }

        $user->qrCodes()->create([
            'link_id' => $link->id,
            'name' => $data['name'],
            'options' => $options,
        ]);

        return back()->with('status', __('QR code created.'));
    }

    public function update(Request $request, QrCode $qrCode)
    {
        abort_unless($qrCode->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'name' => 'required|string|max:60',
            'link_id' => 'required|integer',
            'fg' => 'nullable|string|max:9',
            'bg' => 'nullable|string|max:9',
            'ec_level' => 'nullable|in:low,medium,quartile,high',
            'frame_text' => 'nullable|string|max:40',
            'logo' => 'nullable|image|max:1024',
        ]);

        // Dynamic QR: the destination link can be swapped after printing.
        $link = $request->user()->links()->findOrFail($data['link_id']);

        $options = array_merge($qrCode->options ?? [], [
            'fg' => $data['fg'] ?? '#000000',
            'bg' => $data['bg'] ?? '#ffffff',
            'ec_level' => $data['ec_level'] ?? 'medium',
            'frame_text' => $data['frame_text'] ?? '',
        ]);
        if ($request->hasFile('logo')) {
            abort_unless($this->limits->hasFeature($request->user(), 'qr_logo'), 403);
            $options['logo'] = $request->file('logo')->store('qr-logos', setting('storage_disk', 'public'));
        }

        $qrCode->update(['name' => $data['name'], 'link_id' => $link->id, 'options' => $options]);

        return back()->with('status', __('QR code updated.'));
    }

    public function destroy(Request $request, QrCode $qrCode)
    {
        abort_unless($qrCode->user_id === $request->user()->id, 403);
        $qrCode->delete();

        return back()->with('status', __('QR code deleted.'));
    }

    /** Render/download in png | svg | pdf. QR data points at the short URL with ?qr=1 for scan tracking. */
    public function render(Request $request, QrCode $qrCode, string $format)
    {
        abort_unless($qrCode->user_id === $request->user()->id, 403);
        $link = $qrCode->link;
        abort_unless($link, 404, __('The linked short URL no longer exists.'));

        $data = $link->shortUrl() . '?qr=1';
        $options = $qrCode->options ?? [];
        $download = $request->boolean('download');
        $filename = \Illuminate\Support\Str::slug($qrCode->name) ?: 'qr-code';

        return match ($format) {
            'svg' => response($this->qr->svg($data, $options), 200, [
                'Content-Type' => 'image/svg+xml',
                'Content-Disposition' => ($download ? 'attachment' : 'inline') . "; filename={$filename}.svg",
            ]),
            'pdf' => response($this->qr->pdf($data, $options, $qrCode->name), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename={$filename}.pdf",
            ]),
            default => response($this->qr->png($data, $options), 200, [
                'Content-Type' => 'image/png',
                'Content-Disposition' => ($download ? 'attachment' : 'inline') . "; filename={$filename}.png",
            ]),
        };
    }

    /** Ad-hoc preview used by the QR designer (no saved model). */
    public function preview(Request $request)
    {
        $link = $request->user()->links()->find($request->query('link_id'));
        $data = $link ? $link->shortUrl() . '?qr=1' : rtrim(config('app.url'), '/');

        return response($this->qr->svg($data, [
            'fg' => $request->query('fg', '#000000'),
            'bg' => $request->query('bg', '#ffffff'),
            'ec_level' => $request->query('ec_level', 'medium'),
        ]), 200, ['Content-Type' => 'image/svg+xml', 'Cache-Control' => 'no-store']);
    }
}
