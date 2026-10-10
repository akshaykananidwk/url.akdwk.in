<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function notice()
    {
        return view('auth.verify-email');
    }

    public function verify(Request $request, int $id, string $hash)
    {
        $user = User::findOrFail($id);

        abort_unless(hash_equals(sha1($user->email), $hash), 403);
        abort_unless($request->hasValidSignature(), 403);

        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
            hook_action('user_verified', $user);
        }

        return redirect()->route('dashboard')->with('status', __('Your email has been verified.'));
    }

    public function resend(Request $request)
    {
        if ($request->user()->email_verified_at) {
            return redirect()->route('dashboard');
        }

        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['email' => __('We could not send the email right now. Please try again later or contact support.')]);
        }

        return back()->with('status', __('A new verification link has been sent to your email address.'));
    }
}
