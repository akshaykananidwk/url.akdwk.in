<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AlertChannel;
use App\Models\UtmTemplate;
use Illuminate\Http\Request;

/**
 * User integrations hub: UTM templates + click-alert channels
 * (Slack / Discord / Telegram).
 */
class IntegrationController extends Controller
{
    public function index(Request $request)
    {
        return view('user.integrations.index', [
            'templates' => $request->user()->utmTemplates()->orderBy('name')->get(),
            'channels' => $request->user()->alertChannels()->orderBy('created_at')->get(),
            'channelTypes' => AlertChannel::TYPES,
        ]);
    }

    /* ------------------------------------------------------- UTM templates */

    public function storeTemplate(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:60',
            'source' => 'nullable|string|max:100',
            'medium' => 'nullable|string|max:100',
            'campaign' => 'nullable|string|max:100',
            'term' => 'nullable|string|max:100',
            'content' => 'nullable|string|max:100',
        ]);
        $request->user()->utmTemplates()->create($data);

        return back()->with('status', __('UTM template saved.'));
    }

    public function destroyTemplate(Request $request, UtmTemplate $template)
    {
        abort_unless($template->user_id === $request->user()->id, 403);
        $template->delete();

        return back()->with('status', __('Template deleted.'));
    }

    /* ------------------------------------------------------- alert channels */

    public function storeChannel(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:slack,discord,telegram',
            'target' => 'required|string|max:500',
            'instant' => 'sometimes|boolean',
            'milestone' => 'nullable|integer|min:0|max:1000000',
        ]);
        $data['instant'] = $request->boolean('instant');
        $data['milestone'] = (int) ($data['milestone'] ?? 0);
        $data['active'] = true;
        $request->user()->alertChannels()->create($data);

        return back()->with('status', __('Alert channel added.'));
    }

    public function toggleChannel(Request $request, AlertChannel $channel)
    {
        abort_unless($channel->user_id === $request->user()->id, 403);
        $channel->update(['active' => ! $channel->active]);

        return back()->with('status', __('Channel updated.'));
    }

    public function testChannel(Request $request, AlertChannel $channel)
    {
        abort_unless($channel->user_id === $request->user()->id, 403);
        \App\Jobs\SendClickAlert::dispatchSync($request->user()->id, 0, 'instant', 0);

        // dispatchSync with linkId 0 finds no link; send a direct test instead
        try {
            match ($channel->type) {
                'slack' => \Illuminate\Support\Facades\Http::timeout(5)->post($channel->target, ['text' => '✅ Test alert from ' . site_name()]),
                'discord' => \Illuminate\Support\Facades\Http::timeout(5)->post($channel->target, ['content' => '✅ Test alert from ' . site_name()]),
                'telegram' => $this->telegramTest($channel->target),
                default => null,
            };
        } catch (\Throwable $e) {
            return back()->withErrors(['test' => __('Test failed: ') . $e->getMessage()]);
        }

        return back()->with('status', __('Test message sent.'));
    }

    protected function telegramTest(string $target): void
    {
        [$token, $chatId] = array_pad(explode('|', $target, 2), 2, null);
        if ($token && $chatId) {
            \Illuminate\Support\Facades\Http::timeout(5)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId, 'text' => '✅ Test alert from ' . site_name(),
            ]);
        }
    }

    public function destroyChannel(Request $request, AlertChannel $channel)
    {
        abort_unless($channel->user_id === $request->user()->id, 403);
        $channel->delete();

        return back()->with('status', __('Channel removed.'));
    }

    /* --------------------------------------------------------------- AI */

    /** AJAX: suggest an alias + tags for a destination (used by the link form). */
    public function aiSuggest(Request $request, \App\Services\AiService $ai)
    {
        $data = $request->validate(['destination' => 'required|string|max:2000', 'title' => 'nullable|string|max:200']);
        if (! $ai->enabled()) {
            return response()->json(['enabled' => false], 422);
        }

        return response()->json([
            'enabled' => true,
            'alias' => $ai->suggestAlias($data['destination'], $data['title'] ?? null),
            'tags' => $ai->suggestTags($data['destination'], $data['title'] ?? null),
        ]);
    }
}
