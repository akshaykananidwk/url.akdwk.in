<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\WaitlistEntry;
use Illuminate\Http\Request;

class WaitlistController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'feature' => ['nullable', 'string', 'max:60'],
        ]);

        $feature = $data['feature'] ?: 'general';

        WaitlistEntry::firstOrCreate(
            ['email' => $data['email'], 'feature' => $feature],
            ['ip' => $request->ip()],
        );

        return back()->with('status', __('You are on the list! We will email you.'));
    }
}
