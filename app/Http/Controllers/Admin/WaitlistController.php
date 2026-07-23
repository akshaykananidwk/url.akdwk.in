<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WaitlistEntry;
use Illuminate\Http\Request;

class WaitlistController extends Controller
{
    public function index(Request $request)
    {
        $query = WaitlistEntry::query();

        if ($feature = $request->query('feature')) {
            $query->where('feature', $feature);
        }

        // Counts per feature for the stat cards at the top.
        $byFeature = WaitlistEntry::query()
            ->selectRaw('feature, COUNT(*) as total')
            ->groupBy('feature')
            ->orderByDesc('total')
            ->pluck('total', 'feature');

        return view('admin.waitlist.index', [
            'entries' => $query->orderByDesc('created_at')->paginate(30)->withQueryString(),
            'byFeature' => $byFeature,
            'total' => WaitlistEntry::count(),
        ]);
    }
}
