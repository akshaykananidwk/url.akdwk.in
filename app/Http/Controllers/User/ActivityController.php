<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\TeamMember;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // The user's own actions + actions in any workspace they own.
        $workspaceIds = TeamMember::where('owner_id', $user->id)->pluck('user_id')->push($user->id);

        $activities = Activity::with('user')
            ->where(function ($q) use ($user, $workspaceIds) {
                $q->where('user_id', $user->id)
                    ->orWhere('workspace_id', $user->id)
                    ->orWhereIn('user_id', $workspaceIds);
            })
            ->orderByDesc('created_at')->paginate(40);

        return view('user.activity.index', ['activities' => $activities]);
    }
}
