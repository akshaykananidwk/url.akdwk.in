<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbuseReport;
use App\Models\AuditLog;
use App\Models\BioPage;
use App\Models\BlockedDomain;
use App\Models\BlockedWord;
use App\Models\Domain;
use App\Models\Link;
use App\Models\Pixel;
use App\Models\QrCode;
use App\Models\Space;
use Illuminate\Http\Request;

/**
 * Moderation of all user content: links, spaces, domains, pixels, QR codes,
 * bio pages, abuse reports, plus the blocked domains/words lists.
 */
class ModerationController extends Controller
{
    public function links(Request $request)
    {
        $query = Link::with(['user', 'domain'])->orderByDesc('created_at');
        if ($q = $request->query('q')) {
            $query->where(fn ($w) => $w->where('alias', 'like', "%{$q}%")->orWhere('destination', 'like', "%{$q}%"));
        }
        if ($request->filled('user')) {
            $query->where('user_id', $request->query('user'));
        }

        return view('admin.moderation.links', ['links' => $query->paginate(25)->withQueryString()]);
    }

    public function toggleLink(Link $link)
    {
        $link->update(['disabled' => ! $link->disabled]);
        AuditLog::record($link->disabled ? 'link.disabled' : 'link.enabled', $link);

        return back()->with('status', __('Link updated.'));
    }

    public function destroyLink(Link $link)
    {
        app(\App\Services\LinkService::class)->delete($link);
        AuditLog::record('link.deleted', null, ['alias' => $link->alias]);

        return back()->with('status', __('Link deleted.'));
    }

    public function spaces(Request $request)
    {
        return view('admin.moderation.spaces', [
            'spaces' => Space::with('user')->withCount('links')->orderByDesc('created_at')->paginate(25),
        ]);
    }

    public function destroySpace(Space $space)
    {
        $space->links()->update(['space_id' => null]);
        $space->delete();

        return back()->with('status', __('Space deleted.'));
    }

    public function domains(Request $request)
    {
        return view('admin.moderation.domains', [
            'domains' => Domain::with('user')->withCount('links')->orderByDesc('created_at')->paginate(25),
        ]);
    }

    /** Admin-managed global domains (available to all users). */
    public function storeDomain(Request $request)
    {
        $data = $request->validate([
            'domain' => 'required|string|max:190|regex:/^(?!\-)(?:[a-zA-Z0-9\-]{1,63}\.)+[a-zA-Z]{2,}$/|unique:domains,domain',
            'index_redirect' => 'nullable|url',
        ]);

        Domain::create($data + ['user_id' => null, 'verified_at' => now()]);
        AuditLog::record('domain.created', null, ['domain' => $data['domain']]);

        return back()->with('status', __('Global domain added.'));
    }

    public function destroyDomain(Domain $domain)
    {
        abort_if($domain->links()->exists(), 422, __('This domain still has links.'));
        $domain->delete();

        return back()->with('status', __('Domain deleted.'));
    }

    public function pixels()
    {
        return view('admin.moderation.pixels', [
            'pixels' => Pixel::with('user')->orderByDesc('created_at')->paginate(25),
        ]);
    }

    public function destroyPixel(Pixel $pixel)
    {
        $pixel->links()->detach();
        $pixel->delete();

        return back()->with('status', __('Pixel deleted.'));
    }

    public function qrCodes()
    {
        return view('admin.moderation.qr', [
            'codes' => QrCode::with('user', 'link')->orderByDesc('created_at')->paginate(25),
        ]);
    }

    public function destroyQr(QrCode $qrCode)
    {
        $qrCode->delete();

        return back()->with('status', __('QR code deleted.'));
    }

    public function bioPages()
    {
        return view('admin.moderation.bio', [
            'pages' => BioPage::with('user')->withCount('blocks')->orderByDesc('created_at')->paginate(25),
        ]);
    }

    public function toggleBioPage(BioPage $bioPage)
    {
        $bioPage->update(['active' => ! $bioPage->active]);

        return back()->with('status', __('Bio page updated.'));
    }

    public function destroyBioPage(BioPage $bioPage)
    {
        $bioPage->blocks()->delete();
        $bioPage->subscribers()->delete();
        $bioPage->delete();

        return back()->with('status', __('Bio page deleted.'));
    }

    /* --------------------------------------------------- abuse + blocklists */

    public function reports(Request $request)
    {
        $query = AbuseReport::with('link.user')->orderByDesc('created_at');
        if ($request->query('status')) {
            $query->where('status', $request->query('status'));
        }

        return view('admin.moderation.reports', ['reports' => $query->paginate(25)->withQueryString()]);
    }

    public function resolveReport(Request $request, AbuseReport $report)
    {
        $action = $request->input('action');
        if ($action === 'disable' && $report->link) {
            $report->link->update(['disabled' => true]);
        }
        $report->update(['status' => $action === 'dismiss' ? 'dismissed' : 'resolved']);
        AuditLog::record('report.' . $report->status, $report);

        return back()->with('status', __('Report updated.'));
    }

    public function blocklist()
    {
        return view('admin.moderation.blocklist', [
            'domains' => BlockedDomain::orderBy('domain')->get(),
            'words' => BlockedWord::orderBy('word')->get(),
        ]);
    }

    public function storeBlockedDomain(Request $request)
    {
        $data = $request->validate(['domain' => 'required|string|max:190|unique:blocked_domains,domain', 'reason' => 'nullable|string|max:190']);
        BlockedDomain::create(['domain' => strtolower($data['domain']), 'reason' => $data['reason'] ?? null]);

        return back()->with('status', __('Domain blocked.'));
    }

    public function destroyBlockedDomain(BlockedDomain $blockedDomain)
    {
        $blockedDomain->delete();

        return back()->with('status', __('Domain unblocked.'));
    }

    public function storeBlockedWord(Request $request)
    {
        $data = $request->validate(['word' => 'required|string|max:100|unique:blocked_words,word']);
        BlockedWord::create(['word' => strtolower($data['word'])]);

        return back()->with('status', __('Word blocked.'));
    }

    public function destroyBlockedWord(BlockedWord $blockedWord)
    {
        $blockedWord->delete();

        return back()->with('status', __('Word removed.'));
    }
}
