<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Link;
use App\Services\LinkService;
use App\Services\QrService;
use App\Services\StatsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * REST API v1. Authenticated with API keys (Authorization: Bearer sk_...).
 * Interactive documentation lives at /developers/docs; a Postman collection
 * ships in /docs/postman_collection.json.
 */
class ApiController extends Controller
{
    public function __construct(
        protected LinkService $linkService,
        protected StatsService $stats,
    ) {
    }

    /* ------------------------------------------------------------- links */

    public function listLinks(Request $request)
    {
        $links = $request->user()->links()
            ->with('domain')
            ->search($request->query('q'))
            ->when($request->query('space_id'), fn ($q, $v) => $q->where('space_id', $v))
            ->orderByDesc('created_at')
            ->paginate(min(100, (int) $request->query('per_page', 25)));

        return response()->json([
            'data' => $links->getCollection()->map(fn ($l) => $this->linkResource($l)),
            'meta' => ['total' => $links->total(), 'page' => $links->currentPage(), 'last_page' => $links->lastPage()],
        ]);
    }

    public function createLink(Request $request)
    {
        $data = $request->validate([
            'destination' => 'required|string|max:5000',
            'alias' => 'nullable|string|max:100',
            'title' => 'nullable|string|max:190',
            'domain_id' => 'nullable|integer',
            'space_id' => 'nullable|integer',
            'password' => 'nullable|string|max:190',
            'expires_at' => 'nullable|date',
            'max_clicks' => 'nullable|integer|min:1',
            'utm' => 'nullable|array',
            'targeting' => 'nullable|array',
        ]);

        try {
            $link = $this->linkService->create($request->user(), $data);
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first(), 'errors' => $e->errors()], 422);
        }

        return response()->json(['data' => $this->linkResource($link)], 201);
    }

    public function showLink(Request $request, Link $link)
    {
        $this->authorizeLink($request, $link);

        return response()->json(['data' => $this->linkResource($link)]);
    }

    public function updateLink(Request $request, Link $link)
    {
        $this->authorizeLink($request, $link);

        $data = $request->validate([
            'destination' => 'sometimes|string|max:5000',
            'alias' => 'sometimes|string|max:100',
            'title' => 'nullable|string|max:190',
            'space_id' => 'nullable|integer',
            'disabled' => 'sometimes|boolean',
            'expires_at' => 'nullable|date',
            'max_clicks' => 'nullable|integer|min:1',
            'utm' => 'nullable|array',
            'targeting' => 'nullable|array',
        ]);

        try {
            $this->linkService->update($link, $data);
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first(), 'errors' => $e->errors()], 422);
        }

        return response()->json(['data' => $this->linkResource($link->refresh())]);
    }

    public function deleteLink(Request $request, Link $link)
    {
        $this->authorizeLink($request, $link);
        $this->linkService->delete($link);

        return response()->json(['message' => 'Link deleted.']);
    }

    /* ------------------------------------------------------------- stats */

    public function linkStats(Request $request, Link $link)
    {
        $this->authorizeLink($request, $link);

        $from = $request->filled('from') ? Carbon::parse($request->query('from'))->startOfDay() : now()->subDays(29)->startOfDay();
        $to = $request->filled('to') ? Carbon::parse($request->query('to'))->endOfDay() : now()->endOfDay();

        $payload = [
            'totals' => $this->stats->totals($link, null, $from, $to),
            'series' => $this->stats->series($link, null, $from, $to),
        ];
        foreach (['country', 'referer', 'os', 'browser', 'device', 'language'] as $dim) {
            $payload['breakdowns'][$dim] = $this->stats->breakdown($link, null, $from, $to, $dim);
        }

        return response()->json(['data' => $payload]);
    }

    /* ------------------------------------------------ spaces + domains + QR */

    public function listSpaces(Request $request)
    {
        return response()->json([
            'data' => $request->user()->spaces()->withCount('links')->get()
                ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'color' => $s->color, 'links' => $s->links_count]),
        ]);
    }

    public function listDomains(Request $request)
    {
        $domains = \App\Models\Domain::where(fn ($q) => $q->whereNull('user_id')
            ->orWhere(fn ($q2) => $q2->where('user_id', $request->user()->id)->whereNotNull('verified_at')))->get();

        return response()->json([
            'data' => $domains->map(fn ($d) => ['id' => $d->id, 'domain' => $d->domain, 'global' => $d->isGlobal(), 'ssl' => $d->ssl]),
        ]);
    }

    public function qr(Request $request, Link $link, QrService $qr)
    {
        $this->authorizeLink($request, $link);
        $format = $request->query('format', 'png');
        $options = [
            'fg' => $request->query('fg', '#000000'),
            'bg' => $request->query('bg', '#ffffff'),
            'size' => (int) $request->query('size', 400),
            'ec_level' => $request->query('ec_level', 'medium'),
        ];
        $data = $link->shortUrl() . '?qr=1';

        return $format === 'svg'
            ? response($qr->svg($data, $options), 200, ['Content-Type' => 'image/svg+xml'])
            : response($qr->png($data, $options), 200, ['Content-Type' => 'image/png']);
    }

    /** Account overview (used by browser extensions). */
    public function me(Request $request)
    {
        $user = $request->user();
        $plan = $user->currentPlan();

        return response()->json(['data' => [
            'name' => $user->name,
            'email' => $user->email,
            'plan' => $plan->name,
            'links' => $user->links()->count(),
            'clicks' => (int) $user->links()->sum('clicks_count'),
        ]]);
    }

    /* ------------------------------------------------------------------ */

    protected function authorizeLink(Request $request, Link $link): void
    {
        abort_unless($link->user_id === $request->user()->id, 404, 'Link not found.');
    }

    protected function linkResource(Link $link): array
    {
        return [
            'id' => $link->id,
            'alias' => $link->alias,
            'short_url' => $link->shortUrl(),
            'destination' => $link->destination,
            'title' => $link->title,
            'space_id' => $link->space_id,
            'domain_id' => $link->domain_id,
            'disabled' => $link->disabled,
            'expires_at' => $link->expires_at?->toIso8601String(),
            'max_clicks' => $link->max_clicks,
            'clicks' => $link->clicks_count,
            'unique_clicks' => $link->unique_clicks_count,
            'qr_scans' => $link->qr_scans_count,
            'created_at' => $link->created_at?->toIso8601String(),
        ];
    }
}
