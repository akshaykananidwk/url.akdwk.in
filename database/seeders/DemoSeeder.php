<?php

namespace Database\Seeders;

use App\Models\Click;
use App\Models\ClickRollup;
use App\Models\Link;
use App\Models\Pixel;
use App\Models\Space;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Optional demo data (installer checkbox): a demo user, sample spaces, links
 * and 14 days of realistic click history so charts have something to show.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstWhere('role', 'admin') ?? User::first();
        if (! $user) {
            return;
        }

        $spaces = collect([
            ['name' => 'Marketing', 'color' => '#6366f1', 'icon' => 'megaphone'],
            ['name' => 'Social Media', 'color' => '#ec4899', 'icon' => 'share'],
        ])->map(fn ($s) => Space::firstOrCreate(['user_id' => $user->id, 'name' => $s['name']], $s));

        $pixel = Pixel::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Demo GA4'],
            ['type' => 'ga4', 'value' => 'G-DEMO12345']
        );

        $samples = [
            ['destination' => 'https://laravel.com/docs', 'title' => 'Laravel Documentation'],
            ['destination' => 'https://tailwindcss.com', 'title' => 'Tailwind CSS'],
            ['destination' => 'https://github.com', 'title' => 'GitHub'],
            ['destination' => 'https://news.ycombinator.com', 'title' => 'Hacker News'],
            ['destination' => 'https://developer.mozilla.org', 'title' => 'MDN Web Docs'],
        ];

        $countries = ['IN', 'US', 'GB', 'DE', 'BR', 'ID', 'JP', 'FR'];
        $osList = ['Android', 'iOS', 'Windows', 'macOS', 'Linux'];
        $browsers = ['Chrome', 'Safari', 'Firefox', 'Edge'];
        $devices = ['mobile', 'mobile', 'desktop', 'tablet'];
        $referers = ['google.com', 'twitter.com', 'facebook.com', 'direct', 'linkedin.com'];

        foreach ($samples as $i => $sample) {
            $link = Link::firstOrCreate(
                ['user_id' => $user->id, 'alias' => 'demo' . ($i + 1), 'domain_id' => null],
                $sample + ['space_id' => $spaces[$i % 2]->id, 'public_stats' => $i === 0, 'tags' => ['demo']]
            );
            if ($i === 0) {
                $link->pixels()->syncWithoutDetaching([$pixel->id]);
            }

            $totalClicks = 0;
            $totalUniques = 0;
            for ($d = 13; $d >= 0; $d--) {
                $date = now()->subDays($d)->toDateString();
                $dayClicks = random_int(3, 40);
                $dayUniques = (int) ceil($dayClicks * 0.7);
                $breakdown = [];
                for ($c = 0; $c < $dayClicks; $c++) {
                    foreach ([
                        'country' => $countries[array_rand($countries)],
                        'os' => $osList[array_rand($osList)],
                        'browser' => $browsers[array_rand($browsers)],
                        'device' => $devices[array_rand($devices)],
                        'referer' => $referers[array_rand($referers)],
                        'language' => ['en', 'hi', 'gu', 'es'][array_rand([0, 1, 2, 3])],
                        'hour' => (string) random_int(0, 23),
                    ] as $dim => $key) {
                        $breakdown[$dim][$key] = ($breakdown[$dim][$key] ?? 0) + 1;
                    }
                }

                ClickRollup::updateOrCreate(
                    ['link_id' => $link->id, 'date' => $date],
                    ['user_id' => $user->id, 'clicks' => $dayClicks, 'uniques' => $dayUniques, 'qr_scans' => random_int(0, 4), 'breakdown' => $breakdown]
                );
                $totalClicks += $dayClicks;
                $totalUniques += $dayUniques;
            }

            // A few raw clicks for the live feed.
            for ($c = 0; $c < 15; $c++) {
                Click::create([
                    'link_id' => $link->id,
                    'user_id' => $user->id,
                    'ip_hash' => hash('sha256', Str::random(12)),
                    'country' => $countries[array_rand($countries)],
                    'city' => ['Mumbai', 'Ahmedabad', 'London', 'Berlin', 'New York'][array_rand([0, 1, 2, 3, 4])],
                    'language' => 'en',
                    'os' => $osList[array_rand($osList)],
                    'browser' => $browsers[array_rand($browsers)],
                    'device' => $devices[array_rand($devices)],
                    'referer_host' => $referers[array_rand($referers)],
                    'is_unique' => (bool) random_int(0, 1),
                    'is_qr' => random_int(0, 9) === 0,
                    'created_at' => now()->subMinutes(random_int(1, 2000)),
                ]);
            }

            Link::withoutEvents(fn () => $link->update([
                'clicks_count' => $totalClicks,
                'unique_clicks_count' => $totalUniques,
                'qr_scans_count' => random_int(5, 30),
                'last_click_at' => now()->subMinutes(random_int(1, 300)),
            ]));
        }
    }
}
