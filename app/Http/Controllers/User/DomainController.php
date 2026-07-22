<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Services\PlanLimits;
use Illuminate\Http\Request;

class DomainController extends Controller
{
    public function __construct(protected PlanLimits $limits)
    {
    }

    public function index(Request $request)
    {
        return view('user.domains.index', [
            'domains' => $request->user()->domains()->withCount('links')->orderBy('domain')->paginate(20),
            'appHost' => parse_url(config('app.url'), PHP_URL_HOST),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($this->limits->hasFeature($user, 'custom_domains'), 403, __('Custom domains are not available on your plan.'));
        abort_unless($this->limits->canCreate($user, 'domains'), 403, __('You have reached the domain limit of your plan.'));

        $data = $request->validate([
            'domain' => 'required|string|max:190|regex:/^(?!\-)(?:[a-zA-Z0-9\-]{1,63}\.)+[a-zA-Z]{2,}$/|unique:domains,domain',
        ]);

        $user->domains()->create(['domain' => strtolower($data['domain'])]);

        return back()->with('status', __('Domain added. Point its DNS to this server, then click Verify.'));
    }

    /**
     * DNS verification: the domain must resolve (A/AAAA or CNAME chain) to the
     * same address as the main app host, or serve our verification token.
     */
    public function verify(Request $request, Domain $domain)
    {
        abort_unless($domain->user_id === $request->user()->id, 403);

        $appHost = (string) parse_url(config('app.url'), PHP_URL_HOST);
        $expected = array_merge(gethostbynamel($appHost) ?: [], []);
        $actual = gethostbynamel($domain->domain) ?: [];

        $verified = $expected && $actual && array_intersect($expected, $actual);

        // CNAME record check as a fallback.
        if (! $verified) {
            $cname = dns_get_record($domain->domain, DNS_CNAME);
            foreach ($cname ?: [] as $record) {
                if (strcasecmp(rtrim($record['target'] ?? '', '.'), $appHost) === 0) {
                    $verified = true;
                    break;
                }
            }
        }

        // HTTP token check as a last resort (works behind proxies/CDNs).
        if (! $verified) {
            try {
                $res = \Illuminate\Support\Facades\Http::timeout(5)->get('http://' . $domain->domain . '/.well-known/shortl-verify');
                $verified = trim($res->body()) === $domain->verification_token;
            } catch (\Throwable) {
            }
        }

        if (! $verified) {
            return back()->withErrors(['verify' => __('DNS verification failed. Make sure the domain points to this server (see instructions) and DNS has propagated.')]);
        }

        $ssl = false;
        try {
            $ssl = \Illuminate\Support\Facades\Http::timeout(5)->get('https://' . $domain->domain . '/up')->successful();
        } catch (\Throwable) {
        }

        $domain->update(['verified_at' => now(), 'ssl' => $ssl]);

        return back()->with('status', __('Domain verified successfully.'));
    }

    public function update(Request $request, Domain $domain)
    {
        abort_unless($domain->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'index_redirect' => 'nullable|url|max:2000',
            'not_found_redirect' => 'nullable|url|max:2000',
            'is_default' => 'sometimes|boolean',
        ]);

        if ($request->boolean('is_default')) {
            $request->user()->update(['default_domain' => $domain->domain]);
        }
        $domain->update([
            'index_redirect' => $data['index_redirect'] ?? null,
            'not_found_redirect' => $data['not_found_redirect'] ?? null,
        ]);

        return back()->with('status', __('Domain updated.'));
    }

    public function destroy(Request $request, Domain $domain)
    {
        abort_unless($domain->user_id === $request->user()->id, 403);
        abort_if($domain->links()->exists(), 422, __('Delete or move the links using this domain first.'));
        $domain->delete();

        return back()->with('status', __('Domain removed.'));
    }
}
