<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Webhook;
use App\Services\PlanLimits;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** API keys + webhooks management ("Developers" section). */
class DeveloperController extends Controller
{
    public function __construct(protected PlanLimits $limits)
    {
    }

    public function index(Request $request)
    {
        return view('user.developers.index', [
            'keys' => $request->user()->apiKeys()->orderByDesc('created_at')->get(),
            'webhooks' => $request->user()->webhooks()->orderByDesc('created_at')->get(),
            'events' => Webhook::EVENTS,
            'apiEnabled' => $this->limits->hasFeature($request->user(), 'api'),
            'webhooksEnabled' => $this->limits->hasFeature($request->user(), 'webhooks'),
        ]);
    }

    public function storeKey(Request $request)
    {
        abort_unless($this->limits->hasFeature($request->user(), 'api'), 403, __('API access is not available on your plan.'));
        $request->validate(['name' => 'required|string|max:60']);

        [, $plain] = ApiKey::generate($request->user()->id, $request->input('name'));

        return back()->with('status', __('API key created. Copy it now — it will not be shown again.'))->with('new_api_key', $plain);
    }

    public function destroyKey(Request $request, ApiKey $key)
    {
        abort_unless($key->user_id === $request->user()->id, 403);
        $key->delete();

        return back()->with('status', __('API key revoked.'));
    }

    public function storeWebhook(Request $request)
    {
        abort_unless($this->limits->hasFeature($request->user(), 'webhooks'), 403, __('Webhooks are not available on your plan.'));

        $data = $request->validate([
            'url' => 'required|url|max:500',
            'events' => 'required|array|min:1',
            'events.*' => 'in:' . implode(',', array_keys(Webhook::EVENTS)),
        ]);

        $request->user()->webhooks()->create($data + ['secret' => Str::random(40), 'active' => true]);

        return back()->with('status', __('Webhook created.'));
    }

    public function toggleWebhook(Request $request, Webhook $webhook)
    {
        abort_unless($webhook->user_id === $request->user()->id, 403);
        $webhook->update(['active' => ! $webhook->active, 'failures' => 0]);

        return back()->with('status', $webhook->active ? __('Webhook enabled.') : __('Webhook disabled.'));
    }

    public function destroyWebhook(Request $request, Webhook $webhook)
    {
        abort_unless($webhook->user_id === $request->user()->id, 403);
        $webhook->delete();

        return back()->with('status', __('Webhook deleted.'));
    }
}
