<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BadgeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return view('user.badges.index', [
            'user' => $user,
            'definitions' => config('badges', []),
            'earned' => $user->badges()->get()->keyBy('badge_key'),
        ]);
    }
}
