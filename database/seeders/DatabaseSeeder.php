<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\Language;
use App\Models\Page;
use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Base data required for a working installation: languages, plans, default
 * CMS pages, FAQs. Idempotent — safe to run repeatedly.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'en', 'name' => 'English', 'rtl' => false, 'is_default' => true],
            ['code' => 'gu', 'name' => 'ગુજરાતી', 'rtl' => false, 'is_default' => false],
            ['code' => 'hi', 'name' => 'हिन्दी', 'rtl' => false, 'is_default' => false],
        ] as $lang) {
            Language::updateOrCreate(['code' => $lang['code']], $lang + ['active' => true]);
        }

        Plan::updateOrCreate(['slug' => 'free'], [
            'name' => 'Free',
            'description' => 'Everything you need to get started.',
            'price_monthly' => 0, 'price_yearly' => 0, 'price_lifetime' => 0,
            'is_free' => true, 'is_default' => true, 'active' => true, 'sort_order' => 0,
            'limits' => [
                'links' => 50, 'clicks_per_month' => 5000, 'spaces' => 2, 'domains' => 0,
                'pixels' => 1, 'team_members' => 0, 'qr_codes' => 5, 'bio_pages' => 1,
                'api_rate' => 30, 'retention_days' => 30,
            ],
            'features' => [
                'custom_alias' => true, 'qr' => true, 'bio' => true, 'utm' => true,
                'expiration' => true, 'public_stats' => true, 'bulk' => false,
            ],
        ]);

        Plan::updateOrCreate(['slug' => 'pro'], [
            'name' => 'Pro',
            'description' => 'For creators and marketers who need power features.',
            'price_monthly' => 9, 'price_yearly' => 90, 'price_lifetime' => 0,
            'trial_days' => 7, 'is_free' => false, 'is_featured' => true, 'active' => true, 'sort_order' => 1,
            'limits' => [
                'links' => 2000, 'clicks_per_month' => 100000, 'spaces' => 20, 'domains' => 3,
                'pixels' => 10, 'team_members' => 3, 'qr_codes' => 100, 'bio_pages' => 5,
                'api_rate' => 120, 'retention_days' => 365,
            ],
            'features' => array_fill_keys(array_keys(Plan::FEATURE_KEYS), true),
        ]);

        Plan::updateOrCreate(['slug' => 'business'], [
            'name' => 'Business',
            'description' => 'Unlimited scale for teams and agencies.',
            'price_monthly' => 29, 'price_yearly' => 290, 'price_lifetime' => 699,
            'is_free' => false, 'active' => true, 'sort_order' => 2,
            'limits' => array_fill_keys(array_keys(Plan::LIMIT_KEYS), -1),
            'features' => array_fill_keys(array_keys(Plan::FEATURE_KEYS), true),
        ]);

        foreach ([
            ['title' => 'Terms of Service', 'slug' => 'terms', 'content' => '<h2>Terms of Service</h2><p>By using this service you agree to use it lawfully. Shortened links must not point to malware, phishing, or illegal content. We may disable links or accounts that violate these terms. The service is provided as-is without warranty.</p>'],
            ['title' => 'Privacy Policy', 'slug' => 'privacy', 'content' => '<h2>Privacy Policy</h2><p>We store the data you provide (account details, links) and click analytics (anonymized IP hash, country, device, browser, referrer) to operate the service. We do not sell personal data. You can export or erase your data at any time from your account settings.</p>'],
            ['title' => 'About', 'slug' => 'about', 'content' => '<h2>About us</h2><p>We build simple, powerful link management tools: short links, QR codes, bio pages and analytics — all self-hosted and under your control.</p>'],
        ] as $page) {
            Page::updateOrCreate(['slug' => $page['slug']], $page + ['active' => true, 'show_in_footer' => true]);
        }

        foreach ([
            ['question' => 'What is a URL shortener?', 'answer' => 'It turns long, unwieldy links into short, memorable ones — and tracks every click with detailed analytics (country, device, referrer and more).', 'sort_order' => 1],
            ['question' => 'Can I use my own domain?', 'answer' => 'Yes. Paid plans let you connect custom domains: point your DNS at our server, verify, and every short link can use your brand.', 'sort_order' => 2],
            ['question' => 'Are QR codes dynamic?', 'answer' => 'Yes — QR codes point at your short link, so you can change the destination anytime without reprinting anything.', 'sort_order' => 3],
            ['question' => 'Is there an API?', 'answer' => 'A full REST API with per-plan rate limits is included, plus webhooks for click events.', 'sort_order' => 4],
            ['question' => 'Can I cancel anytime?', 'answer' => 'Absolutely. Cancel auto-renewal with one click; your plan stays active until the end of the billing period.', 'sort_order' => 5],
        ] as $faq) {
            Faq::updateOrCreate(['question' => $faq['question']], $faq + ['active' => true]);
        }
    }
}
