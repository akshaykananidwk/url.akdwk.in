<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\Link;
use App\Services\LinkService;
use App\Services\PlanLimits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LinkController extends Controller
{
    public function __construct(
        protected LinkService $links,
        protected PlanLimits $limits,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $query = $user->links()->with(['domain', 'space'])
            ->search($request->query('q'));

        if ($request->filled('space')) {
            $query->where('space_id', $request->query('space'));
        }
        if ($request->filled('domain')) {
            $query->where('domain_id', $request->query('domain') === 'main' ? null : $request->query('domain'));
        }
        if ($request->query('status') === 'active') {
            $query->active();
        } elseif ($request->query('status') === 'disabled') {
            $query->where('disabled', true);
        } elseif ($request->query('status') === 'archived') {
            $query->whereNotNull('archived_at');
        } else {
            $query->whereNull('archived_at');
        }
        if ($request->filled('tag')) {
            $query->where('tags', 'like', '%"' . str_replace(['%', '_'], '', $request->query('tag')) . '"%');
        }

        $sort = in_array($request->query('sort'), ['created_at', 'clicks_count', 'alias', 'last_click_at'], true)
            ? $request->query('sort') : 'created_at';
        $query->orderBy($sort, $request->query('dir') === 'asc' ? 'asc' : 'desc');

        return view('user.links.index', [
            'links' => $query->paginate(15)->withQueryString(),
            'spaces' => $user->spaces()->orderBy('name')->get(),
            'domains' => $this->availableDomains($user),
        ]);
    }

    public function create(Request $request)
    {
        return view('user.links.form', [
            'link' => null,
            'spaces' => $request->user()->spaces()->orderBy('name')->get(),
            'domains' => $this->availableDomains($request->user()),
            'pixels' => $request->user()->pixels()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $link = $this->links->create($request->user(), $data);

        if ($request->expectsJson()) {
            return response()->json(['id' => $link->id, 'short_url' => $link->shortUrl()], 201);
        }

        return redirect()->route('links.edit', $link)->with('status', __('Link created.'))->with('created_link', $link->shortUrl());
    }

    public function edit(Request $request, Link $link)
    {
        Gate::authorize('update', $link);

        return view('user.links.form', [
            'link' => $link->load('pixels'),
            'spaces' => $request->user()->spaces()->orderBy('name')->get(),
            'domains' => $this->availableDomains($request->user()),
            'pixels' => $request->user()->pixels()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Link $link)
    {
        Gate::authorize('update', $link);

        $data = $this->validated($request, $link);
        if (($data['password'] ?? null) === '__keep__') {
            unset($data['password']);
        }
        $this->links->update($link, $data);

        return redirect()->route('links.edit', $link)->with('status', __('Link updated.'));
    }

    public function destroy(Request $request, Link $link)
    {
        Gate::authorize('delete', $link);
        $this->links->delete($link);

        return redirect()->route('links.index')->with('status', __('Link deleted.'));
    }

    /** Toggle enable/disable. */
    public function toggle(Request $request, Link $link)
    {
        Gate::authorize('update', $link);
        $link->update(['disabled' => ! $link->disabled]);

        return back()->with('status', $link->disabled ? __('Link disabled.') : __('Link enabled.'));
    }

    public function archive(Request $request, Link $link)
    {
        Gate::authorize('update', $link);
        $link->update(['archived_at' => $link->archived_at ? null : now()]);

        return back()->with('status', $link->archived_at ? __('Link archived.') : __('Link restored.'));
    }

    public function duplicate(Request $request, Link $link)
    {
        Gate::authorize('update', $link);
        $this->links->guardQuota($request->user());
        $copy = $this->links->duplicate($link);

        return redirect()->route('links.edit', $copy)->with('status', __('Link duplicated.'));
    }

    /** Move link to another space. */
    public function move(Request $request, Link $link)
    {
        Gate::authorize('update', $link);
        $spaceId = $request->input('space_id') ?: null;
        if ($spaceId && ! $request->user()->spaces()->where('id', $spaceId)->exists()) {
            abort(422);
        }
        $link->update(['space_id' => $spaceId]);

        return back()->with('status', __('Link moved.'));
    }

    /** Ping this link's destination and update its health badge. */
    public function checkHealth(Request $request, Link $link)
    {
        Gate::authorize('update', $link);
        \Illuminate\Support\Facades\Artisan::call('app:check-link-health', ['--link' => $link->id]);

        return back()->with('status', __('Link health checked.'));
    }

    public function bulkDelete(Request $request)
    {
        $ids = array_filter((array) $request->input('ids', []));
        $links = $request->user()->links()->whereIn('id', $ids)->get();
        foreach ($links as $link) {
            $this->links->delete($link);
        }

        return back()->with('status', __(':count links deleted.', ['count' => $links->count()]));
    }

    /* ------------------------------------------------------------- Bulk + CSV */

    public function bulkForm(Request $request)
    {
        abort_unless($this->limits->hasFeature($request->user(), 'bulk'), 403, __('Bulk shortening is not available on your plan.'));

        return view('user.links.bulk');
    }

    public function bulkStore(Request $request)
    {
        $user = $request->user();
        abort_unless($this->limits->hasFeature($user, 'bulk'), 403);

        $request->validate(['urls' => 'required|string']);
        $urls = collect(preg_split('/[\r\n]+/', (string) $request->input('urls')))
            ->map(fn ($u) => trim($u))->filter()->unique()->take(200);

        $this->links->guardQuota($user, $urls->count());

        $created = [];
        $errors = [];
        foreach ($urls as $url) {
            try {
                $link = $this->links->create($user, ['destination' => $url, 'space_id' => $request->input('space_id')]);
                $created[] = $link;
            } catch (ValidationException $e) {
                $errors[$url] = implode(' ', collect($e->errors())->flatten()->all());
            }
        }

        return view('user.links.bulk', ['results' => $created, 'failures' => $errors]);
    }

    public function importCsv(Request $request)
    {
        $user = $request->user();
        abort_unless($this->limits->hasFeature($user, 'bulk'), 403);
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:2048']);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = null;
        $created = 0;
        $failed = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if ($header === null) {
                $header = array_map(fn ($h) => strtolower(trim((string) $h)), $row);
                // headerless file whose first cell is already a URL
                if (! in_array('destination', $header, true) && ! in_array('url', $header, true)) {
                    $header = ['destination'];
                    $row = [$row[0]];
                } else {
                    continue;
                }
            }
            $data = array_combine($header, array_pad(array_slice($row, 0, count($header)), count($header), null));
            $dest = $data['destination'] ?? $data['url'] ?? null;
            if (! $dest) {
                continue;
            }
            try {
                $this->links->guardQuota($user);
                $this->links->create($user, [
                    'destination' => $dest,
                    'alias' => $data['alias'] ?? null,
                    'title' => $data['title'] ?? null,
                ]);
                $created++;
            } catch (ValidationException) {
                $failed++;
            }
        }
        fclose($handle);

        return redirect()->route('links.index')->with('status', __(':created links imported, :failed failed.', ['created' => $created, 'failed' => $failed]));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        abort_unless($this->limits->hasFeature($request->user(), 'export'), 403, __('Export is not available on your plan.'));
        $user = $request->user();

        return response()->streamDownload(function () use ($user) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['alias', 'short_url', 'destination', 'title', 'clicks', 'unique_clicks', 'created_at']);
            $user->links()->with('domain')->chunk(500, function ($links) use ($out) {
                foreach ($links as $link) {
                    fputcsv($out, [
                        $link->alias, $link->shortUrl(), $link->destination, $link->title,
                        $link->clicks_count, $link->unique_clicks_count, $link->created_at?->toDateTimeString(),
                    ]);
                }
            });
            fclose($out);
        }, 'links-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }

    /* ------------------------------------------------------------------ */

    protected function validated(Request $request, ?Link $link = null): array
    {
        $data = $request->validate([
            'destination' => 'required|string|max:5000',
            'alias' => 'nullable|string|max:100',
            'title' => 'nullable|string|max:190',
            'domain_id' => 'nullable|integer',
            'space_id' => 'nullable|integer',
            'password' => 'nullable|string|max:190',
            'expires_at' => 'nullable|date',
            'starts_at' => 'nullable|date',
            'conversion_goal' => 'nullable|string|max:190',
            'max_clicks' => 'nullable|integer|min:1',
            'expired_redirect' => 'nullable|url|max:2000',
            'disabled' => 'sometimes|boolean',
            'cloaking' => 'sometimes|boolean',
            'public_stats' => 'sometimes|boolean',
            'notes' => 'nullable|string|max:5000',
            'tags' => 'nullable|string|max:500',
            'deep_link' => 'nullable|array',
            'og' => 'nullable|array',
            'og.title' => 'nullable|string|max:190',
            'og.description' => 'nullable|string|max:500',
            'og.image' => 'nullable|url|max:1000',
            'utm' => 'nullable|array',
            'targeting' => 'nullable|array',
            'pixel_ids' => 'nullable|array',
        ]);

        $data['disabled'] = $request->boolean('disabled');
        $data['cloaking'] = $request->boolean('cloaking');
        $data['public_stats'] = $request->boolean('public_stats');
        $data['tags'] = collect(explode(',', (string) ($data['tags'] ?? '')))
            ->map(fn ($t) => trim($t))->filter()->unique()->values()->all() ?: null;

        // Normalize targeting arrays: drop empty rule rows.
        if (isset($data['targeting'])) {
            foreach (['country', 'platform', 'language', 'device'] as $group) {
                $data['targeting'][$group] = array_values(array_filter(
                    $data['targeting'][$group] ?? [],
                    fn ($r) => ! empty($r['key']) && ! empty($r['url'])
                ));
            }
            $data['targeting']['time'] = array_values(array_filter(
                $data['targeting']['time'] ?? [],
                fn ($r) => ! empty($r['url'])
            ));
            $data['targeting']['rotation'] = array_values(array_filter(
                $data['targeting']['rotation'] ?? [],
                fn ($r) => ! empty($r['url']) && (int) ($r['weight'] ?? 0) > 0
            ));
            $data['targeting'] = array_filter($data['targeting']) ?: null;
        }

        if (isset($data['deep_link'])) {
            $data['deep_link']['enabled'] = ! empty($data['deep_link']['enabled']);
            if (! $data['deep_link']['enabled']) {
                $data['deep_link'] = null;
            }
        }
        foreach (['og', 'utm'] as $group) {
            if (isset($data[$group])) {
                $data[$group] = array_filter($data[$group]) ?: null;
            }
        }

        return $data;
    }

    protected function availableDomains($user)
    {
        return Domain::where(function ($q) use ($user) {
            $q->whereNull('user_id')->orWhere(fn ($q2) => $q2->where('user_id', $user->id)->whereNotNull('verified_at'));
        })->orderBy('domain')->get();
    }
}
