<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Page;
use App\Models\Plan;
use App\Models\Post;
use App\Services\LinkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class LandingController extends Controller
{
    public function index(Request $request)
    {
        return view('landing.index', [
            'plans' => Plan::where('active', true)->orderBy('sort_order')->get(),
            'faqs' => Faq::where('active', true)->orderBy('sort_order')->get(),
            'totalLinks' => \App\Models\Link::count(),
            'totalClicks' => (int) \App\Models\Link::sum('clicks_count'),
            'totalUsers' => \App\Models\User::count(),
        ]);
    }

    /** Instant-shorten box on the landing hero (works without login). */
    public function guestShorten(Request $request, LinkService $links)
    {
        abort_unless(setting('guest_shorten_enabled', true), 403);

        $request->validate(['destination' => 'required|string|max:5000']);

        // Guest links belong to the system user (first admin) and expire per settings.
        $owner = \App\Models\User::where('role', 'admin')->orderBy('id')->first();
        abort_unless($owner, 503);

        try {
            $destination = $links->validateDestination($request->input('destination'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        }

        $link = \App\Models\Link::create([
            'user_id' => $owner->id,
            'alias' => $links->generateAlias(null),
            'destination' => $destination,
            'expires_at' => now()->addDays((int) setting('guest_link_days', 30)),
            'meta' => ['guest' => true, 'ip' => $request->ip()],
        ]);

        return response()->json(['short_url' => $link->shortUrl()]);
    }

    public function pricing()
    {
        return view('landing.pricing', [
            'plans' => Plan::where('active', true)->orderBy('sort_order')->get(),
            'faqs' => Faq::where('active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function page(string $slug)
    {
        $page = Page::where('slug', $slug)->where('active', true)->firstOrFail();

        return view('landing.page', ['page' => $page]);
    }

    public function blog()
    {
        return view('landing.blog', [
            'posts' => Post::where('published', true)->orderByDesc('published_at')->paginate(9),
        ]);
    }

    public function blogPost(string $slug)
    {
        $post = Post::where('slug', $slug)->where('published', true)->firstOrFail();

        return view('landing.blog-post', ['post' => $post]);
    }

    public function contact()
    {
        return view('landing.contact');
    }

    public function contactSubmit(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:190',
            'message' => 'required|string|max:5000',
        ]);
        \App\Http\Controllers\Auth\LoginController::verifyCaptcha($request);

        $to = setting('contact_email', setting('mail_from_address'));
        if ($to) {
            try {
                Mail::raw(
                    "From: {$data['name']} <{$data['email']}>\n\n{$data['message']}",
                    fn ($m) => $m->to($to)->replyTo($data['email'])->subject('[' . site_name() . '] Contact form')
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return back()->with('status', __('Thanks! Your message has been sent.'));
    }

    public function reportAbuse()
    {
        return view('landing.report');
    }

    public function reportAbuseSubmit(Request $request)
    {
        $data = $request->validate([
            'url' => 'required|string|max:500',
            'email' => 'nullable|email|max:190',
            'reason' => 'required|in:phishing,malware,spam,scam,inappropriate,copyright,other',
            'details' => 'nullable|string|max:5000',
        ]);
        \App\Http\Controllers\Auth\LoginController::verifyCaptcha($request);

        $linkId = null;
        $path = trim((string) parse_url($data['url'], PHP_URL_PATH), '/');
        if ($path) {
            $linkId = \App\Models\Link::where('alias', $path)->value('id');
        }

        \App\Models\AbuseReport::create($data + ['link_id' => $linkId]);

        return back()->with('status', __('Report submitted. Our team will review it shortly.'));
    }

    /** robots.txt + sitemap.xml generated from settings/content. */
    public function robots()
    {
        $content = setting('robots_txt', "User-agent: *\nDisallow: /admin\nDisallow: /dashboard\nSitemap: " . url('/sitemap.xml'));

        return response($content, 200, ['Content-Type' => 'text/plain']);
    }

    public function sitemap()
    {
        // Static, high-value landing surfaces (batch #6 growth pages).
        $programmaticServices = ['youtube', 'amazon', 'instagram', 'tiktok', 'facebook', 'twitter', 'linkedin', 'whatsapp', 'spotify', 'github'];
        $freeTools = ['', 'qr-code', 'utm-builder', 'bulk-shortener', 'qr-scanner', 'password-generator', 'link-expander', 'og-preview', 'business-card'];

        $urls = collect([url('/'), route('pricing'), route('blog'), route('directory.index'), route('leaderboard.index'), route('status.index')])
            ->merge(collect($freeTools)->map(fn ($t) => rtrim(url('/free-tools/' . $t), '/')))
            ->merge(collect($programmaticServices)->map(fn ($s) => url('/shorten/' . $s)))
            ->merge(Page::where('active', true)->pluck('slug')->map(fn ($s) => url('/page/' . $s)))
            ->merge(Post::where('published', true)->pluck('slug')->map(fn ($s) => url('/blog/' . $s)))
            // Public creator bio pages — a large, growing set of indexable URLs.
            ->merge(\App\Models\BioPage::where('active', true)->orderByDesc('views')->limit(5000)->pluck('username')->map(fn ($u) => url('/@' . $u)))
            ->unique()->values();

        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            . $urls->map(fn ($u) => '<url><loc>' . e($u) . '</loc></url>')->implode('')
            . '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    /** RSS 2.0 feed of the latest published blog posts. */
    public function blogFeed()
    {
        $posts = Post::where('published', true)->orderByDesc('published_at')->limit(30)->get();
        $items = $posts->map(function ($post) {
            $link = url('/blog/' . $post->slug);
            $date = optional($post->published_at)->toRfc2822String();
            $desc = htmlspecialchars((string) ($post->excerpt ?: strip_tags((string) $post->content)), ENT_XML1);

            return "<item><title>" . htmlspecialchars((string) $post->title, ENT_XML1) . "</title>"
                . "<link>{$link}</link><guid>{$link}</guid>"
                . ($date ? "<pubDate>{$date}</pubDate>" : '')
                . "<description>{$desc}</description></item>";
        })->implode('');

        $xml = '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"><channel>'
            . '<title>' . htmlspecialchars(site_name() . ' — Blog', ENT_XML1) . '</title>'
            . '<link>' . e(route('blog')) . '</link>'
            . '<description>' . htmlspecialchars((string) setting('tagline', ''), ENT_XML1) . '</description>'
            . $items . '</channel></rss>';

        return response($xml, 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }

    public function manifest()
    {
        return response()->json([
            'name' => site_name(),
            'short_name' => site_name(),
            'start_url' => '/dashboard',
            'display' => 'standalone',
            'background_color' => '#0f172a',
            'theme_color' => '#6366f1',
            'icons' => [
                ['src' => setting('pwa_icon_192', '/img/icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => setting('pwa_icon_512', '/img/icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png'],
            ],
        ]);
    }
}
