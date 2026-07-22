<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\PlanLimits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TeamController extends Controller
{
    public function __construct(protected PlanLimits $limits)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        return view('user.team.index', [
            'members' => $user->teamMembers()->with('user')->orderBy('created_at')->get(),
            'workspaces' => TeamMember::with('owner')->where('user_id', $user->id)->whereNotNull('accepted_at')->get(),
            'roles' => TeamMember::ROLES,
        ]);
    }

    public function invite(Request $request)
    {
        $user = $request->user();
        abort_unless($this->limits->hasFeature($user, 'team'), 403, __('Team members are not available on your plan.'));
        abort_unless($this->limits->canCreate($user, 'team_members'), 403, __('You have reached the team member limit of your plan.'));

        $data = $request->validate([
            'email' => 'required|email|max:190',
            'role' => 'required|in:admin,editor,viewer',
        ]);

        abort_if(strcasecmp($data['email'], $user->email) === 0, 422, __('You cannot invite yourself.'));
        abort_if($user->teamMembers()->where('email', $data['email'])->exists(), 422, __('This person is already invited.'));

        $member = $user->teamMembers()->create([
            'email' => strtolower($data['email']),
            'role' => $data['role'],
            'invite_token' => Str::random(40),
            'user_id' => User::where('email', $data['email'])->value('id'),
        ]);

        $inviteUrl = route('team.accept', $member->invite_token);
        try {
            Mail::raw(
                __(':owner invited you to join their :site workspace as :role. Accept the invitation: :url', [
                    'owner' => $user->name, 'site' => site_name(), 'role' => TeamMember::ROLES[$member->role], 'url' => $inviteUrl,
                ]),
                fn ($m) => $m->to($member->email)->subject(__('Workspace invitation — :site', ['site' => site_name()]))
            );
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('status', __('Invitation sent.'))->with('invite_url', $inviteUrl);
    }

    public function accept(Request $request, string $token)
    {
        $member = TeamMember::where('invite_token', $token)->whereNull('accepted_at')->firstOrFail();

        if (! $request->user()) {
            session(['url.intended' => $request->fullUrl()]);

            return redirect()->route('login')->with('status', __('Log in or create an account with :email to accept the invitation.', ['email' => $member->email]));
        }

        abort_unless(strcasecmp($request->user()->email, $member->email) === 0, 403, __('This invitation was sent to a different email address.'));

        $member->update(['user_id' => $request->user()->id, 'accepted_at' => now(), 'invite_token' => null]);

        return redirect()->route('team.index')->with('status', __('You joined the workspace.'));
    }

    public function updateRole(Request $request, TeamMember $member)
    {
        abort_unless($member->owner_id === $request->user()->id, 403);
        $request->validate(['role' => 'required|in:admin,editor,viewer']);
        $member->update(['role' => $request->input('role')]);

        return back()->with('status', __('Role updated.'));
    }

    public function destroy(Request $request, TeamMember $member)
    {
        abort_unless($member->owner_id === $request->user()->id || $member->user_id === $request->user()->id, 403);
        $member->delete();

        return back()->with('status', __('Member removed.'));
    }
}
