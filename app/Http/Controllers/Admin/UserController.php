<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('plan')->withCount('links');

        if ($q = $request->query('q')) {
            $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"));
        }
        if ($request->query('status') === 'suspended') {
            $query->whereNotNull('suspended_at');
        }
        if ($request->query('role')) {
            $query->where('role', $request->query('role'));
        }
        if ($request->query('plan')) {
            $query->where('plan_id', $request->query('plan'));
        }

        return view('admin.users.index', [
            'users' => $query->orderByDesc('created_at')->paginate(20)->withQueryString(),
            'plans' => Plan::orderBy('sort_order')->get(),
        ]);
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', [
            'user' => $user->loadCount(['links', 'spaces', 'domains', 'bioPages']),
            'plans' => Plan::orderBy('sort_order')->get(),
            'payments' => $user->payments()->orderByDesc('created_at')->limit(10)->get(),
            'adminPermissions' => self::ADMIN_PERMISSIONS,
        ]);
    }

    /** Available staff permission keys shown in the role editor. */
    public const ADMIN_PERMISSIONS = [
        'admin.users' => 'Manage users',
        'admin.links' => 'Moderate links',
        'admin.payments' => 'Manage payments',
        'admin.plans' => 'Manage plans & coupons',
        'admin.content' => 'Manage content (pages, blog, FAQ)',
        'admin.settings' => 'Manage settings',
        'admin.reports' => 'View reports',
    ];

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:190|unique:users,email,' . $user->id,
            'role' => 'required|in:user,staff,admin',
            'permissions' => 'nullable|array',
            'plan_id' => 'nullable|exists:plans,id',
            'plan_cycle' => 'nullable|in:monthly,yearly,lifetime',
            'plan_expires_at' => 'nullable|date',
            'password' => 'nullable|string|min:8',
        ]);

        // Never let a staff admin escalate anyone (including themselves) to admin.
        if (! $request->user()->isSuperAdmin()) {
            unset($data['role'], $data['permissions']);
        }
        // The last super admin cannot be demoted.
        if ($user->role === 'admin' && ($data['role'] ?? 'admin') !== 'admin'
            && User::where('role', 'admin')->count() === 1) {
            return back()->withErrors(['role' => __('You cannot demote the last administrator.')]);
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }
        $data['permissions'] = ($data['role'] ?? $user->role) === 'staff' ? array_keys($data['permissions'] ?? []) : null;

        // role/permissions are intentionally not mass-assignable — set explicitly.
        $user->forceFill(array_intersect_key($data, array_flip(['role', 'permissions'])));
        $user->fill(array_diff_key($data, array_flip(['role', 'permissions'])))->save();
        AuditLog::record('user.updated', $user, ['fields' => array_keys($data)]);

        return back()->with('status', __('User updated.'));
    }

    public function verifyEmail(User $user)
    {
        $user->forceFill(['email_verified_at' => now()])->save();
        AuditLog::record('user.verified', $user);

        return back()->with('status', __('Email marked as verified.'));
    }

    public function suspend(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 422, __('You cannot suspend yourself.'));
        abort_if($user->role === 'admin', 422, __('Administrators cannot be suspended.'));

        $user->forceFill(['suspended_at' => $user->suspended_at ? null : now()])->save();
        // Cached links keep serving until flushed.
        foreach ($user->links()->get(['id', 'domain_id', 'alias']) as $link) {
            $link->flushCache();
        }
        AuditLog::record($user->suspended_at ? 'user.suspended' : 'user.unsuspended', $user);

        return back()->with('status', $user->suspended_at ? __('User suspended.') : __('User unsuspended.'));
    }

    public function impersonate(Request $request, User $user)
    {
        abort_if($user->isAdmin(), 422, __('You cannot impersonate another admin.'));

        $adminId = $request->user()->id;
        AuditLog::record('user.impersonated', $user);
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('impersonator', $adminId);

        return redirect()->route('dashboard')->with('status', __('You are now logged in as :name. Log out to return to your admin account.', ['name' => $user->name]));
    }

    public function destroy(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 422, __('You cannot delete yourself.'));
        abort_if($user->role === 'admin' && User::where('role', 'admin')->count() === 1, 422, __('You cannot delete the last administrator.'));

        foreach ($user->links as $link) {
            app(\App\Services\LinkService::class)->delete($link);
        }
        $user->spaces()->delete();
        $user->domains()->delete();
        $user->pixels()->delete();
        $user->qrCodes()->delete();
        foreach ($user->bioPages as $page) {
            $page->blocks()->delete();
            $page->delete();
        }
        $user->apiKeys()->delete();
        $user->webhooks()->delete();
        $user->teamMembers()->delete();
        $user->memberships()->delete();
        $user->delete();

        AuditLog::record('user.deleted', null, ['email' => $user->email]);

        return redirect()->route('admin.users.index')->with('status', __('User deleted.'));
    }
}
