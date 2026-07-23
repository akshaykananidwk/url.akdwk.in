<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SsoProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SsoController extends Controller
{
    public function index()
    {
        return view('admin.sso.index', ['providers' => SsoProvider::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['name']) . '-' . Str::lower(Str::random(4));
        $data['active'] = true;
        SsoProvider::create($data);
        AuditLog::record('sso.created', null, ['name' => $data['name']]);

        return back()->with('status', __('SSO provider added.'));
    }

    public function update(Request $request, SsoProvider $provider)
    {
        $data = $this->validated($request);
        if (($data['client_secret'] ?? null) === '••••••••') {
            unset($data['client_secret']);
        }
        $provider->update($data);

        return back()->with('status', __('SSO provider updated.'));
    }

    public function toggle(SsoProvider $provider)
    {
        $provider->update(['active' => ! $provider->active]);

        return back()->with('status', __('Provider updated.'));
    }

    public function destroy(SsoProvider $provider)
    {
        $provider->delete();

        return back()->with('status', __('Provider removed.'));
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:60',
            'client_id' => 'required|string|max:255',
            'client_secret' => 'nullable|string|max:500',
            'issuer' => 'nullable|url|max:255',
            'authorize_url' => 'nullable|url|max:255',
            'token_url' => 'nullable|url|max:255',
            'userinfo_url' => 'nullable|url|max:255',
            'scopes' => 'nullable|string|max:190',
        ]);
    }
}
