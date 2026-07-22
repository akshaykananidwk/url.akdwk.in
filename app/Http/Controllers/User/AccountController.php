<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return view('user.account.index', [
            'user' => $user,
            'sessions' => DB::table('sessions')->where('user_id', $user->id)->orderByDesc('last_activity')->limit(20)->get(),
            'logins' => $user->loginHistories()->orderByDesc('created_at')->limit(15)->get(),
            'currentSessionId' => $request->session()->getId(),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:190|unique:users,email,' . $user->id,
            'locale' => 'nullable|string|max:10',
            'timezone' => 'nullable|timezone',
            'theme' => 'nullable|in:light,dark,system',
            'avatar' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', setting('storage_disk', 'public'));
        } else {
            unset($data['avatar']);
        }

        if ($data['email'] !== $user->email && setting('require_email_verification', false)) {
            $user->forceFill(['email_verified_at' => null])->save();
        }
        $user->update($data);
        if (! empty($data['locale'])) {
            session(['locale' => $data['locale']]);
        }

        return back()->with('status', __('Profile updated.'));
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $request->user()->update(['password' => $request->input('password')]);

        return back()->with('status', __('Password changed.'));
    }

    public function updateNotifications(Request $request)
    {
        $prefs = [
            'email_link_reports' => $request->boolean('email_link_reports'),
            'email_billing' => $request->boolean('email_billing'),
            'email_product' => $request->boolean('email_product'),
            'inapp_clicks_milestones' => $request->boolean('inapp_clicks_milestones'),
        ];
        $request->user()->update(['notification_prefs' => $prefs]);

        return back()->with('status', __('Notification preferences saved.'));
    }

    /* ------------------------------------------------------------- 2FA */

    public function twoFactorSetup(Request $request)
    {
        $user = $request->user();
        if ($user->hasTwoFactorEnabled()) {
            return back();
        }

        $secret = Totp::generateSecret();
        session(['2fa_setup_secret' => $secret]);

        $uri = Totp::provisioningUri($secret, $user->email, site_name());
        $qrSvg = app(\App\Services\QrService::class)->svg($uri, ['size' => 220]);

        return view('user.account.two-factor', ['secret' => $secret, 'qrSvg' => $qrSvg]);
    }

    public function twoFactorConfirm(Request $request)
    {
        $request->validate(['code' => 'required|string']);
        $secret = session('2fa_setup_secret');
        abort_unless($secret, 400);

        if (! Totp::verify($secret, $request->input('code'))) {
            return back()->withErrors(['code' => __('The provided code is invalid.')]);
        }

        $recovery = collect(range(1, 8))->map(fn () => Str::upper(Str::random(5)) . '-' . Str::upper(Str::random(5)))->all();

        $request->user()->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode($recovery)),
            'two_factor_confirmed_at' => now(),
        ])->save();
        session()->forget('2fa_setup_secret');

        return view('user.account.recovery-codes', ['codes' => $recovery]);
    }

    public function twoFactorDisable(Request $request)
    {
        $request->validate(['password' => 'required|current_password']);

        $request->user()->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return back()->with('status', __('Two-factor authentication disabled.'));
    }

    /* -------------------------------------------------------- sessions */

    public function logoutOtherSessions(Request $request)
    {
        $request->validate(['password' => 'required|current_password']);

        Auth::logoutOtherDevices($request->input('password'));
        DB::table('sessions')->where('user_id', $request->user()->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        return back()->with('status', __('All other sessions have been logged out.'));
    }

    /* ------------------------------------------------- GDPR export/erase */

    public function exportData(Request $request)
    {
        $user = $request->user();
        $payload = [
            'profile' => $user->only(['name', 'email', 'locale', 'timezone', 'created_at']),
            'links' => $user->links()->get(['alias', 'destination', 'title', 'clicks_count', 'created_at']),
            'spaces' => $user->spaces()->get(['name', 'created_at']),
            'domains' => $user->domains()->get(['domain', 'verified_at']),
            'bio_pages' => $user->bioPages()->get(['username', 'title']),
            'payments' => $user->payments()->get(['invoice_number', 'total', 'currency', 'status', 'paid_at']),
            'login_history' => $user->loginHistories()->limit(100)->get(['ip', 'created_at']),
        ];

        return response()->streamDownload(
            fn () => print(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)),
            'my-data-' . now()->format('Y-m-d') . '.json',
            ['Content-Type' => 'application/json']
        );
    }

    public function destroy(Request $request)
    {
        $request->validate(['password' => 'required|current_password', 'confirm_text' => 'required|in:DELETE']);
        $user = $request->user();

        hook_action('user_deleting', $user);

        DB::transaction(function () use ($user) {
            foreach ($user->links as $link) {
                app(\App\Services\LinkService::class)->delete($link);
            }
            $user->spaces()->delete();
            $user->domains()->delete();
            $user->pixels()->delete();
            $user->qrCodes()->delete();
            foreach ($user->bioPages as $page) {
                $page->blocks()->delete();
                $page->subscribers()->delete();
                $page->delete();
            }
            $user->apiKeys()->delete();
            $user->webhooks()->delete();
            $user->teamMembers()->delete();
            $user->memberships()->delete();
            $user->subscriptions()->delete();
            $user->loginHistories()->delete();
            // Payments are retained for accounting but detached from personal data.
            $user->payments()->update(['meta' => null]);
            $user->delete();
        });

        Auth::logout();
        $request->session()->invalidate();

        return redirect('/')->with('status', __('Your account and personal data have been deleted.'));
    }
}
