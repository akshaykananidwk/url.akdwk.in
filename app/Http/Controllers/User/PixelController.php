<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Pixel;
use App\Services\PlanLimits;
use Illuminate\Http\Request;

class PixelController extends Controller
{
    public function __construct(protected PlanLimits $limits)
    {
    }

    public function index(Request $request)
    {
        return view('user.pixels.index', [
            'pixels' => $request->user()->pixels()->withCount('links')->orderBy('name')->paginate(20),
            'types' => Pixel::TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($this->limits->hasFeature($user, 'pixels'), 403, __('Pixels are not available on your plan.'));
        abort_unless($this->limits->canCreate($user, 'pixels'), 403, __('You have reached the pixel limit of your plan.'));

        $data = $request->validate([
            'name' => 'required|string|max:60',
            'type' => 'required|in:' . implode(',', array_keys(Pixel::TYPES)),
            'value' => 'required|string|max:5000',
        ]);
        $user->pixels()->create($data);

        return back()->with('status', __('Pixel created.'));
    }

    public function update(Request $request, Pixel $pixel)
    {
        abort_unless($pixel->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'name' => 'required|string|max:60',
            'type' => 'required|in:' . implode(',', array_keys(Pixel::TYPES)),
            'value' => 'required|string|max:5000',
        ]);
        $pixel->update($data);

        return back()->with('status', __('Pixel updated.'));
    }

    public function destroy(Request $request, Pixel $pixel)
    {
        abort_unless($pixel->user_id === $request->user()->id, 403);
        $pixel->links()->detach();
        $pixel->delete();

        return back()->with('status', __('Pixel deleted.'));
    }
}
