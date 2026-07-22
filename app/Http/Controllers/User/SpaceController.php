<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Space;
use App\Services\PlanLimits;
use Illuminate\Http\Request;

class SpaceController extends Controller
{
    public function __construct(protected PlanLimits $limits)
    {
    }

    public function index(Request $request)
    {
        return view('user.spaces.index', [
            'spaces' => $request->user()->spaces()->withCount('links')->orderBy('name')->paginate(24),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($this->limits->canCreate($request->user(), 'spaces'), 403, __('You have reached the space limit of your plan.'));

        $data = $request->validate([
            'name' => 'required|string|max:60',
            'color' => 'required|string|max:20',
            'icon' => 'required|string|max:40',
        ]);
        $request->user()->spaces()->create($data);

        return back()->with('status', __('Space created.'));
    }

    public function update(Request $request, Space $space)
    {
        abort_unless($space->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'name' => 'required|string|max:60',
            'color' => 'required|string|max:20',
            'icon' => 'required|string|max:40',
        ]);
        $space->update($data);

        return back()->with('status', __('Space updated.'));
    }

    public function destroy(Request $request, Space $space)
    {
        abort_unless($space->user_id === $request->user()->id, 403);
        $space->links()->update(['space_id' => null]);
        $space->delete();

        return back()->with('status', __('Space deleted.'));
    }
}
