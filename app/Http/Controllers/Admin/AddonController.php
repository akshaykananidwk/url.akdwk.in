<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\AddonManager;
use Illuminate\Http\Request;

class AddonController extends Controller
{
    public function __construct(protected AddonManager $addons)
    {
    }

    public function index()
    {
        return view('admin.addons.index', [
            'addons' => $this->addons->all(),
            'addonsPath' => base_path('addons'),
            'settingsPages' => hook_filter('addon_settings_pages', []),
        ]);
    }

    public function toggle(Request $request, string $slug)
    {
        $addon = collect($this->addons->all())->first(fn ($a) => $a['model']->slug === $slug) ?: abort(404);

        if ($addon['model']->enabled) {
            $this->addons->disable($slug);
        } else {
            $this->addons->enable($slug);
        }
        AuditLog::record('addon.' . ($addon['model']->enabled ? 'disabled' : 'enabled'), null, ['slug' => $slug]);

        return back()->with('status', __('Addon updated. Reload the page to apply changes.'));
    }
}
