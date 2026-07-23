<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Link;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    /**
     * Public weekly leaderboard: top public links and top creators by clicks.
     */
    public function index()
    {
        // Top 20 public links — only those whose owners opted into public stats.
        $topLinks = Link::query()
            ->where('disabled', false)
            ->where('public_stats', true)
            ->orderByDesc('clicks_count')
            ->limit(20)
            ->get(['id', 'user_id', 'alias', 'title', 'destination', 'clicks_count', 'unique_clicks_count', 'public_stats']);

        // Top 20 creators by total clicks across their public links.
        $topCreators = DB::table('links')
            ->join('users', 'users.id', '=', 'links.user_id')
            ->where('links.disabled', false)
            ->where('links.public_stats', true)
            ->groupBy('users.id', 'users.name', 'users.avatar', 'users.email')
            ->select([
                'users.id',
                'users.name',
                'users.avatar',
                'users.email',
                DB::raw('SUM(links.clicks_count) as total_clicks'),
                DB::raw('COUNT(links.id) as links_count'),
            ])
            ->orderByDesc('total_clicks')
            ->limit(20)
            ->get();

        return view('site.leaderboard', [
            'topLinks' => $topLinks,
            'topCreators' => $topCreators,
        ]);
    }
}
