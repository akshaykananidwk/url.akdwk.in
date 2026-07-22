<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\BioBlock;
use App\Models\BioPage;
use App\Services\PlanLimits;
use Illuminate\Http\Request;

class BioPageController extends Controller
{
    public const THEMES = ['default', 'midnight', 'sunset', 'forest', 'ocean', 'candy', 'mono'];
    public const FONTS = ['Inter', 'Poppins', 'Roboto', 'Merriweather', 'Space Grotesk', 'DM Sans'];

    public function __construct(protected PlanLimits $limits)
    {
    }

    public function index(Request $request)
    {
        return view('user.bio.index', [
            'pages' => $request->user()->bioPages()->withCount('blocks')->orderBy('username')->paginate(12),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($this->limits->hasFeature($user, 'bio'), 403, __('Bio pages are not available on your plan.'));
        abort_unless($this->limits->canCreate($user, 'bio_pages'), 403, __('You have reached the bio page limit of your plan.'));

        $data = $request->validate([
            'username' => 'required|string|max:60|regex:/^[a-zA-Z0-9_\.\-]+$/|unique:bio_pages,username',
            'title' => 'required|string|max:100',
        ]);

        $page = $user->bioPages()->create($data + ['theme' => 'default']);

        return redirect()->route('bio.edit', $page)->with('status', __('Bio page created.'));
    }

    public function edit(Request $request, BioPage $bioPage)
    {
        abort_unless($bioPage->user_id === $request->user()->id, 403);

        return view('user.bio.edit', [
            'page' => $bioPage->load('blocks'),
            'themes' => self::THEMES,
            'fonts' => self::FONTS,
            'blockTypes' => BioBlock::TYPES,
            'domains' => $request->user()->domains()->whereNotNull('verified_at')->get(),
        ]);
    }

    public function update(Request $request, BioPage $bioPage)
    {
        abort_unless($bioPage->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'username' => 'required|string|max:60|regex:/^[a-zA-Z0-9_\.\-]+$/|unique:bio_pages,username,' . $bioPage->id,
            'title' => 'required|string|max:100',
            'bio' => 'nullable|string|max:1000',
            'theme' => 'nullable|string|max:40',
            'font' => 'nullable|string|max:60',
            'colors' => 'nullable|array',
            'seo' => 'nullable|array',
            'seo.title' => 'nullable|string|max:190',
            'seo.description' => 'nullable|string|max:500',
            'socials' => 'nullable|array',
            'domain_id' => 'nullable|integer',
            'active' => 'sometimes|boolean',
            'avatar' => 'nullable|image|max:2048',
            'cover' => 'nullable|image|max:4096',
        ]);

        if (! empty($data['domain_id']) && ! $request->user()->domains()->whereNotNull('verified_at')->where('id', $data['domain_id'])->exists()) {
            $data['domain_id'] = null;
        }
        foreach (['avatar', 'cover'] as $img) {
            if ($request->hasFile($img)) {
                $data[$img] = $request->file($img)->store('bio', setting('storage_disk', 'public'));
            } else {
                unset($data[$img]);
            }
        }
        $data['active'] = $request->boolean('active');
        $data['socials'] = array_filter($data['socials'] ?? []) ?: null;

        $bioPage->update($data);

        return back()->with('status', __('Bio page saved.'));
    }

    public function destroy(Request $request, BioPage $bioPage)
    {
        abort_unless($bioPage->user_id === $request->user()->id, 403);
        $bioPage->blocks()->delete();
        $bioPage->subscribers()->delete();
        $bioPage->delete();

        return redirect()->route('bio.index')->with('status', __('Bio page deleted.'));
    }

    /* ------------------------------------------------------------ blocks */

    public function storeBlock(Request $request, BioPage $bioPage)
    {
        abort_unless($bioPage->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'type' => 'required|in:' . implode(',', array_keys(BioBlock::TYPES)),
            'content' => 'nullable|array',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
        ]);

        $bioPage->blocks()->create([
            'type' => $data['type'],
            'content' => $data['content'] ?? [],
            'sort_order' => ($bioPage->blocks()->max('sort_order') ?? 0) + 1,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
        ]);

        return back()->with('status', __('Block added.'));
    }

    public function updateBlock(Request $request, BioBlock $block)
    {
        abort_unless($block->page->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'content' => 'nullable|array',
            'active' => 'sometimes|boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date',
        ]);
        $data['active'] = $request->boolean('active', true);
        $block->update($data);

        return back()->with('status', __('Block updated.'));
    }

    public function reorderBlocks(Request $request, BioPage $bioPage)
    {
        abort_unless($bioPage->user_id === $request->user()->id, 403);
        $order = (array) $request->input('order', []);
        foreach (array_values($order) as $i => $id) {
            $bioPage->blocks()->where('id', $id)->update(['sort_order' => $i]);
        }

        return response()->json(['ok' => true]);
    }

    public function destroyBlock(Request $request, BioBlock $block)
    {
        abort_unless($block->page->user_id === $request->user()->id, 403);
        $block->delete();

        return back()->with('status', __('Block removed.'));
    }
}
