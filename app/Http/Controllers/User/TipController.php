<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Tip;
use Illuminate\Http\Request;

/** Tips received across the user's bio pages. */
class TipController extends Controller
{
    public function index(Request $request)
    {
        $tips = Tip::where('user_id', $request->user()->id)
            ->with('bioPage')->orderByDesc('created_at')->paginate(25);

        return view('user.tips.index', [
            'tips' => $tips,
            'totalReceived' => Tip::where('user_id', $request->user()->id)->where('status', 'completed')->sum('amount'),
            'pendingCount' => Tip::where('user_id', $request->user()->id)->where('status', 'pending')->count(),
        ]);
    }
}
