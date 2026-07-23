<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\BioPublicController;
use App\Http\Controllers\Install\InstallController;
use App\Http\Controllers\Install\UpdateController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\RedirectController;
use App\Http\Controllers\Site;
use App\Http\Controllers\User;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Installer + updater
|--------------------------------------------------------------------------
*/
Route::prefix('install')->name('install.')->group(function () {
    Route::get('/', [InstallController::class, 'requirements'])->name('requirements');
    Route::get('/license', [InstallController::class, 'license'])->name('license');
    Route::post('/license', [InstallController::class, 'storeLicense'])->name('license.store');
    Route::get('/database', [InstallController::class, 'database'])->name('database');
    Route::post('/database/test', [InstallController::class, 'testDatabase'])->name('database.test');
    Route::post('/database', [InstallController::class, 'storeDatabase'])->name('database.store');
    Route::get('/admin', [InstallController::class, 'admin'])->name('admin');
    Route::post('/admin', [InstallController::class, 'storeAdmin'])->name('admin.store');
    Route::get('/finish', [InstallController::class, 'finish'])->name('finish');
});

Route::get('/update', [UpdateController::class, 'index'])->name('update');
Route::post('/update', [UpdateController::class, 'run'])->name('update.run');

/*
|--------------------------------------------------------------------------
| Public site
|--------------------------------------------------------------------------
*/
Route::get('/', [RedirectController::class, 'domainIndex'])->name('home');
Route::post('/shorten', [LandingController::class, 'guestShorten'])->middleware('throttle:shorten-guest')->name('guest.shorten');
Route::get('/pricing', [LandingController::class, 'pricing'])->name('pricing');
Route::get('/blog', [LandingController::class, 'blog'])->name('blog');
Route::get('/blog/feed', [LandingController::class, 'blogFeed'])->name('blog.feed');
Route::get('/blog/{slug}', [LandingController::class, 'blogPost'])->name('blog.post');
Route::get('/page/{slug}', [LandingController::class, 'page'])->name('page');
Route::get('/contact', [LandingController::class, 'contact'])->name('contact');
Route::post('/contact', [LandingController::class, 'contactSubmit'])->middleware('throttle:10,1');
Route::get('/report', [LandingController::class, 'reportAbuse'])->name('report');
Route::post('/report', [LandingController::class, 'reportAbuseSubmit'])->middleware('throttle:10,1');
// Serves the DNS-verification token for custom domains proxied through a CDN:
// once the domain routes here, this endpoint proves ownership.
Route::get('/.well-known/shortl-verify', function (\Illuminate\Http\Request $request) {
    $domain = \App\Models\Domain::where('domain', strtolower($request->getHost()))->first();

    return response($domain?->verification_token ?? '', 200, ['Content-Type' => 'text/plain']);
});

// Conversion tracking pixel / beacon (dropped on the merchant's success page)
Route::get('/cv/{alias}.gif', [\App\Http\Controllers\ConversionController::class, 'pixel'])->where('alias', '[a-zA-Z0-9\-_]+')->name('conversion.pixel');
Route::get('/cv/{alias}.js', [\App\Http\Controllers\ConversionController::class, 'beacon'])->where('alias', '[a-zA-Z0-9\-_]+')->name('conversion.beacon');

Route::get('/robots.txt', [LandingController::class, 'robots']);
Route::get('/sitemap.xml', [LandingController::class, 'sitemap']);
Route::get('/manifest.json', [LandingController::class, 'manifest'])->name('manifest');
Route::view('/offline', 'landing.offline')->name('offline');

/*
|--------------------------------------------------------------------------
| Growth & traffic — public pages (batch #6)
|--------------------------------------------------------------------------
| Registered before the /{alias} redirect catch-all so their static paths win.
*/

// SEO — public link preview, bio directory, dynamic OG images, feeds
Route::get('/preview/{alias}', [Site\PreviewController::class, 'show'])->where('alias', '[a-zA-Z0-9\-_]+')->name('preview.show');
Route::get('/directory', [Site\DirectoryController::class, 'index'])->name('directory.index');
Route::get('/directory/feed', [Site\DirectoryController::class, 'feed'])->name('directory.feed');
Route::get('/og/link/{alias}.svg', [Site\OgImageController::class, 'link'])->where('alias', '[a-zA-Z0-9\-_]+')->name('og.link');
Route::get('/og/bio/{username}.svg', [Site\OgImageController::class, 'bio'])->name('og.bio');

// Free public tools (no login) — each ranks on its own SEO landing page
Route::prefix('free-tools')->name('ftools.')->group(function () {
    Route::get('/', [Site\ToolController::class, 'hub'])->name('hub');
    Route::get('/qr-code', [Site\ToolController::class, 'qr'])->name('qr');
    Route::get('/utm-builder', [Site\ToolController::class, 'utm'])->name('utm');
    Route::get('/bulk-shortener', [Site\ToolController::class, 'bulk'])->name('bulk');
    Route::post('/bulk-shortener', [Site\ToolController::class, 'bulkStore'])->middleware('throttle:10,1')->name('bulk.store');
    Route::get('/qr-scanner', [Site\ToolController::class, 'scanner'])->name('scanner');
    Route::get('/password-generator', [Site\ToolController::class, 'password'])->name('password');
    Route::get('/link-expander', [Site\ToolController::class, 'expander'])->name('expander');
    Route::post('/link-expander', [Site\ToolController::class, 'expanderCheck'])->middleware('throttle:20,1')->name('expander.check');
    Route::get('/og-preview', [Site\ToolController::class, 'ogPreview'])->name('og');
    Route::post('/og-preview', [Site\ToolController::class, 'ogPreviewFetch'])->middleware('throttle:20,1')->name('og.check');
    Route::get('/business-card', [Site\ToolController::class, 'vcard'])->name('vcard');
});
// Programmatic SEO — "Shorten {service} links" landing pages
Route::get('/shorten/{service}', [Site\ToolController::class, 'programmatic'])->where('service', '[a-z0-9\-]+')->name('ftools.programmatic');

// Viral — leaderboard, embeddable widgets, waitlist capture
Route::get('/leaderboard', [Site\LeaderboardController::class, 'index'])->name('leaderboard.index');
Route::get('/widget/{alias}.js', [Site\WidgetController::class, 'js'])->where('alias', '[a-zA-Z0-9\-_]+')->name('widget.js');
Route::get('/embed/{alias}', [Site\WidgetController::class, 'embed'])->where('alias', '[a-zA-Z0-9\-_]+')->name('widget.embed');
Route::post('/waitlist', [Site\WaitlistController::class, 'store'])->middleware('throttle:10,1')->name('waitlist.store');

// Public status / uptime page
Route::get('/status', [Site\StatusController::class, 'index'])->name('status.index');

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login');
    Route::get('/two-factor', [LoginController::class, 'twoFactorChallenge'])->name('two-factor.challenge');
    Route::post('/two-factor', [LoginController::class, 'twoFactorVerify'])->middleware('throttle:login');
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->middleware('throttle:register');
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');
    Route::get('/auth/social/{provider}', [SocialAuthController::class, 'redirect'])->name('social.redirect');
    Route::match(['get', 'post'], '/auth/social/{provider}/callback', [SocialAuthController::class, 'callback'])->name('social.callback');
    Route::get('/auth/sso/{slug}', [\App\Http\Controllers\Auth\SsoController::class, 'redirect'])->name('sso.redirect');
    Route::match(['get', 'post'], '/auth/sso/{slug}/callback', [\App\Http\Controllers\Auth\SsoController::class, 'callback'])->name('sso.callback');
});
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/verify-email', [VerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', [VerificationController::class, 'verify'])->middleware('signed')->name('verification.verify');
    Route::post('/verify-email/resend', [VerificationController::class, 'resend'])->middleware('throttle:4,1')->name('verification.resend');
});

/*
|--------------------------------------------------------------------------
| User panel
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'not.suspended', 'verified.setting'])->group(function () {
    Route::get('/dashboard', [User\DashboardController::class, 'index'])->name('dashboard');

    // Links
    Route::get('/links', [User\LinkController::class, 'index'])->name('links.index');
    Route::get('/links/create', [User\LinkController::class, 'create'])->name('links.create');
    Route::post('/links', [User\LinkController::class, 'store'])->name('links.store');
    Route::get('/links/bulk', [User\LinkController::class, 'bulkForm'])->name('links.bulk');
    Route::post('/links/bulk', [User\LinkController::class, 'bulkStore']);
    Route::post('/links/import', [User\LinkController::class, 'importCsv'])->name('links.import');
    Route::get('/links/export', [User\LinkController::class, 'exportCsv'])->name('links.export');
    Route::post('/links/bulk-delete', [User\LinkController::class, 'bulkDelete'])->name('links.bulk-delete');
    Route::get('/links/{link}/edit', [User\LinkController::class, 'edit'])->name('links.edit');
    Route::put('/links/{link}', [User\LinkController::class, 'update'])->name('links.update');
    Route::delete('/links/{link}', [User\LinkController::class, 'destroy'])->name('links.destroy');
    Route::post('/links/{link}/toggle', [User\LinkController::class, 'toggle'])->name('links.toggle');
    Route::post('/links/{link}/archive', [User\LinkController::class, 'archive'])->name('links.archive');
    Route::post('/links/{link}/duplicate', [User\LinkController::class, 'duplicate'])->name('links.duplicate');
    Route::post('/links/{link}/move', [User\LinkController::class, 'move'])->name('links.move');
    Route::post('/links/{link}/check-health', [User\LinkController::class, 'checkHealth'])->name('links.check-health');

    // Spaces
    Route::get('/spaces', [User\SpaceController::class, 'index'])->name('spaces.index');
    Route::post('/spaces', [User\SpaceController::class, 'store'])->name('spaces.store');
    Route::put('/spaces/{space}', [User\SpaceController::class, 'update'])->name('spaces.update');
    Route::delete('/spaces/{space}', [User\SpaceController::class, 'destroy'])->name('spaces.destroy');

    // Domains
    Route::get('/domains', [User\DomainController::class, 'index'])->name('domains.index');
    Route::post('/domains', [User\DomainController::class, 'store'])->name('domains.store');
    Route::post('/domains/{domain}/verify', [User\DomainController::class, 'verify'])->name('domains.verify');
    Route::put('/domains/{domain}', [User\DomainController::class, 'update'])->name('domains.update');
    Route::delete('/domains/{domain}', [User\DomainController::class, 'destroy'])->name('domains.destroy');

    // Pixels
    Route::get('/pixels', [User\PixelController::class, 'index'])->name('pixels.index');
    Route::post('/pixels', [User\PixelController::class, 'store'])->name('pixels.store');
    Route::put('/pixels/{pixel}', [User\PixelController::class, 'update'])->name('pixels.update');
    Route::delete('/pixels/{pixel}', [User\PixelController::class, 'destroy'])->name('pixels.destroy');

    // QR codes
    Route::get('/qr', [User\QrCodeController::class, 'index'])->name('qr.index');
    Route::post('/qr', [User\QrCodeController::class, 'store'])->name('qr.store');
    Route::get('/qr/preview', [User\QrCodeController::class, 'preview'])->name('qr.preview');
    Route::put('/qr/{qrCode}', [User\QrCodeController::class, 'update'])->name('qr.update');
    Route::delete('/qr/{qrCode}', [User\QrCodeController::class, 'destroy'])->name('qr.destroy');
    Route::get('/qr/{qrCode}/{format}', [User\QrCodeController::class, 'render'])->where('format', 'png|svg|pdf')->name('qr.render');

    // Stats
    Route::get('/stats', [User\StatsController::class, 'global'])->name('stats.global');
    Route::get('/stats/live', [User\StatsController::class, 'live'])->name('stats.live');
    Route::get('/stats/link/{link}', [User\StatsController::class, 'link'])->name('stats.link');
    Route::get('/stats/link/{link}/export.csv', [User\StatsController::class, 'exportCsv'])->name('stats.export.csv');
    Route::get('/stats/link/{link}/export.pdf', [User\StatsController::class, 'exportPdf'])->name('stats.export.pdf');

    // Bio pages
    Route::get('/bio', [User\BioPageController::class, 'index'])->name('bio.index');
    Route::post('/bio', [User\BioPageController::class, 'store'])->name('bio.store');
    Route::get('/bio/{bioPage}/edit', [User\BioPageController::class, 'edit'])->name('bio.edit');
    Route::put('/bio/{bioPage}', [User\BioPageController::class, 'update'])->name('bio.update');
    Route::delete('/bio/{bioPage}', [User\BioPageController::class, 'destroy'])->name('bio.destroy');
    Route::post('/bio/{bioPage}/blocks', [User\BioPageController::class, 'storeBlock'])->name('bio.blocks.store');
    Route::post('/bio/{bioPage}/blocks/reorder', [User\BioPageController::class, 'reorderBlocks'])->name('bio.blocks.reorder');
    Route::put('/bio/blocks/{block}', [User\BioPageController::class, 'updateBlock'])->name('bio.blocks.update');
    Route::delete('/bio/blocks/{block}', [User\BioPageController::class, 'destroyBlock'])->name('bio.blocks.destroy');

    // Tools
    Route::get('/tools', [User\ToolsController::class, 'index'])->name('tools.index');
    Route::post('/tools/file', [User\ToolsController::class, 'fileToLink'])->name('tools.file');
    Route::post('/tools/vcard', [User\ToolsController::class, 'vcard'])->name('tools.vcard');
    Route::post('/tools/whatsapp', [User\ToolsController::class, 'whatsapp'])->name('tools.whatsapp');
    Route::post('/tools/app-link', [User\ToolsController::class, 'appLink'])->name('tools.app');
    Route::post('/tools/music', [User\ToolsController::class, 'musicLink'])->name('tools.music');

    // Bio page templates + tips
    Route::post('/bio/{bioPage}/apply-template', [User\BioPageController::class, 'applyTemplate'])->name('bio.apply-template');
    Route::get('/tips', [User\TipController::class, 'index'])->name('tips.index');

    // Team
    Route::get('/team', [User\TeamController::class, 'index'])->name('team.index');
    Route::post('/team/invite', [User\TeamController::class, 'invite'])->name('team.invite');
    Route::put('/team/{member}', [User\TeamController::class, 'updateRole'])->name('team.update');
    Route::delete('/team/{member}', [User\TeamController::class, 'destroy'])->name('team.destroy');

    // Developers: API keys + webhooks + docs
    Route::get('/developers', [User\DeveloperController::class, 'index'])->name('developers.index');
    Route::post('/developers/keys', [User\DeveloperController::class, 'storeKey'])->name('developers.keys.store');
    Route::delete('/developers/keys/{key}', [User\DeveloperController::class, 'destroyKey'])->name('developers.keys.destroy');
    Route::post('/developers/webhooks', [User\DeveloperController::class, 'storeWebhook'])->name('developers.webhooks.store');
    Route::post('/developers/webhooks/{webhook}/toggle', [User\DeveloperController::class, 'toggleWebhook'])->name('developers.webhooks.toggle');
    Route::delete('/developers/webhooks/{webhook}', [User\DeveloperController::class, 'destroyWebhook'])->name('developers.webhooks.destroy');
    Route::view('/developers/docs', 'user.developers.docs')->name('developers.docs');

    // Integrations: UTM templates + click alerts (Slack/Discord/Telegram) + AI
    Route::get('/integrations', [User\IntegrationController::class, 'index'])->name('integrations.index');
    Route::post('/integrations/utm', [User\IntegrationController::class, 'storeTemplate'])->name('integrations.utm.store');
    Route::delete('/integrations/utm/{template}', [User\IntegrationController::class, 'destroyTemplate'])->name('integrations.utm.destroy');
    Route::post('/integrations/channels', [User\IntegrationController::class, 'storeChannel'])->name('integrations.channels.store');
    Route::post('/integrations/channels/{channel}/toggle', [User\IntegrationController::class, 'toggleChannel'])->name('integrations.channels.toggle');
    Route::post('/integrations/channels/{channel}/test', [User\IntegrationController::class, 'testChannel'])->name('integrations.channels.test');
    Route::delete('/integrations/channels/{channel}', [User\IntegrationController::class, 'destroyChannel'])->name('integrations.channels.destroy');
    Route::post('/integrations/connect/{provider}', [User\IntegrationController::class, 'connect'])->name('integrations.connect');
    Route::delete('/integrations/connect/{account}', [User\IntegrationController::class, 'disconnect'])->name('integrations.disconnect');
    Route::post('/links/ai-suggest', [User\IntegrationController::class, 'aiSuggest'])->name('links.ai-suggest');

    // Affiliate
    Route::get('/affiliate', [User\AffiliateController::class, 'index'])->name('affiliate.index');
    Route::post('/affiliate/payout', [User\AffiliateController::class, 'requestPayout'])->name('affiliate.payout');

    // Credits + activity + branding
    Route::get('/credits', [User\CreditController::class, 'index'])->name('credits.index');
    Route::get('/activity', [User\ActivityController::class, 'index'])->name('activity.index');
    Route::put('/account/branding', [User\AccountController::class, 'updateBranding'])->name('account.branding');

    // Account
    Route::get('/account', [User\AccountController::class, 'index'])->name('account.index');
    Route::put('/account/profile', [User\AccountController::class, 'updateProfile'])->name('account.profile');
    Route::put('/account/password', [User\AccountController::class, 'updatePassword'])->name('account.password');
    Route::put('/account/notifications', [User\AccountController::class, 'updateNotifications'])->name('account.notifications');
    Route::get('/account/two-factor', [User\AccountController::class, 'twoFactorSetup'])->name('account.two-factor');
    Route::post('/account/two-factor', [User\AccountController::class, 'twoFactorConfirm'])->name('account.two-factor.confirm');
    Route::delete('/account/two-factor', [User\AccountController::class, 'twoFactorDisable'])->name('account.two-factor.disable');
    Route::post('/account/logout-others', [User\AccountController::class, 'logoutOtherSessions'])->name('account.logout-others');
    Route::get('/account/export', [User\AccountController::class, 'exportData'])->name('account.export');
    Route::delete('/account', [User\AccountController::class, 'destroy'])->name('account.destroy');

    // Billing
    Route::get('/billing/plans', [User\BillingController::class, 'plans'])->name('billing.plans');
    Route::get('/billing/checkout/{plan}', [User\BillingController::class, 'checkout'])->name('billing.checkout');
    Route::post('/billing/checkout/{plan}', [User\BillingController::class, 'pay'])->name('billing.pay');
    Route::match(['get', 'post'], '/billing/return/{gateway}/{payment}', [User\BillingController::class, 'handleReturn'])->name('billing.return');
    Route::get('/billing/invoices', [User\BillingController::class, 'invoices'])->name('billing.invoices');
    Route::get('/billing/invoices/{payment}.pdf', [User\BillingController::class, 'invoicePdf'])->name('billing.invoice.pdf');
    Route::post('/billing/cancel', [User\BillingController::class, 'cancelSubscription'])->name('billing.cancel');
});

// Team invite acceptance (login not required upfront)
Route::get('/invite/{token}', [User\TeamController::class, 'accept'])->name('team.accept');

// Chat bot webhooks (CSRF-exempt via webhooks/* rule) — registered BEFORE the
// billing {gateway} catch-all so their specific paths win.
Route::post('/webhooks/telegram/{secret}', [\App\Http\Controllers\BotController::class, 'telegram'])->name('bots.telegram');
Route::post('/webhooks/slack/command', [\App\Http\Controllers\BotController::class, 'slack'])->name('bots.slack');
Route::post('/webhooks/discord', [\App\Http\Controllers\BotController::class, 'discord'])->name('bots.discord');

// Payment gateway webhooks (CSRF-exempt)
Route::post('/webhooks/{gateway}', [User\BillingController::class, 'webhook'])->name('billing.webhook');

/*
|--------------------------------------------------------------------------
| Admin panel
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [Admin\UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [Admin\UserController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/verify', [Admin\UserController::class, 'verifyEmail'])->name('users.verify');
    Route::post('/users/{user}/suspend', [Admin\UserController::class, 'suspend'])->name('users.suspend');
    Route::post('/users/{user}/impersonate', [Admin\UserController::class, 'impersonate'])->name('users.impersonate');
    Route::delete('/users/{user}', [Admin\UserController::class, 'destroy'])->name('users.destroy');

    // Moderation
    Route::get('/links', [Admin\ModerationController::class, 'links'])->name('links.index');
    Route::post('/links/{link}/toggle', [Admin\ModerationController::class, 'toggleLink'])->name('links.toggle');
    Route::delete('/links/{link}', [Admin\ModerationController::class, 'destroyLink'])->name('links.destroy');
    Route::get('/spaces', [Admin\ModerationController::class, 'spaces'])->name('spaces.index');
    Route::delete('/spaces/{space}', [Admin\ModerationController::class, 'destroySpace'])->name('spaces.destroy');
    Route::get('/domains', [Admin\ModerationController::class, 'domains'])->name('domains.index');
    Route::post('/domains', [Admin\ModerationController::class, 'storeDomain'])->name('domains.store');
    Route::delete('/domains/{domain}', [Admin\ModerationController::class, 'destroyDomain'])->name('domains.destroy');
    Route::get('/pixels', [Admin\ModerationController::class, 'pixels'])->name('pixels.index');
    Route::delete('/pixels/{pixel}', [Admin\ModerationController::class, 'destroyPixel'])->name('pixels.destroy');
    Route::get('/qr', [Admin\ModerationController::class, 'qrCodes'])->name('qr.index');
    Route::delete('/qr/{qrCode}', [Admin\ModerationController::class, 'destroyQr'])->name('qr.destroy');
    Route::get('/bio', [Admin\ModerationController::class, 'bioPages'])->name('bio.index');
    Route::post('/bio/{bioPage}/toggle', [Admin\ModerationController::class, 'toggleBioPage'])->name('bio.toggle');
    Route::delete('/bio/{bioPage}', [Admin\ModerationController::class, 'destroyBioPage'])->name('bio.destroy');
    Route::get('/reports-abuse', [Admin\ModerationController::class, 'reports'])->name('abuse.index');
    Route::post('/reports-abuse/{report}', [Admin\ModerationController::class, 'resolveReport'])->name('abuse.resolve');
    Route::get('/blocklist', [Admin\ModerationController::class, 'blocklist'])->name('blocklist');
    Route::post('/blocklist/domains', [Admin\ModerationController::class, 'storeBlockedDomain'])->name('blocklist.domains.store');
    Route::delete('/blocklist/domains/{blockedDomain}', [Admin\ModerationController::class, 'destroyBlockedDomain'])->name('blocklist.domains.destroy');
    Route::post('/blocklist/words', [Admin\ModerationController::class, 'storeBlockedWord'])->name('blocklist.words.store');
    Route::delete('/blocklist/words/{blockedWord}', [Admin\ModerationController::class, 'destroyBlockedWord'])->name('blocklist.words.destroy');

    // Payments
    Route::get('/payments', [Admin\PaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments/{payment}/approve', [Admin\PaymentController::class, 'approve'])->name('payments.approve');
    Route::post('/payments/{payment}/decline', [Admin\PaymentController::class, 'decline'])->name('payments.decline');
    Route::post('/payments/{payment}/refund', [Admin\PaymentController::class, 'refund'])->name('payments.refund');
    Route::get('/payouts', [Admin\PaymentController::class, 'payouts'])->name('payouts.index');
    Route::put('/payouts/{payout}', [Admin\PaymentController::class, 'updatePayout'])->name('payouts.update');

    // Plans, coupons, taxes
    Route::get('/plans', [Admin\PlanController::class, 'index'])->name('plans.index');
    Route::get('/plans/create', [Admin\PlanController::class, 'create'])->name('plans.create');
    Route::post('/plans', [Admin\PlanController::class, 'store'])->name('plans.store');
    Route::get('/plans/{plan}/edit', [Admin\PlanController::class, 'edit'])->name('plans.edit');
    Route::put('/plans/{plan}', [Admin\PlanController::class, 'update'])->name('plans.update');
    Route::delete('/plans/{plan}', [Admin\PlanController::class, 'destroy'])->name('plans.destroy');
    Route::get('/coupons', [Admin\PlanController::class, 'coupons'])->name('coupons.index');
    Route::post('/coupons', [Admin\PlanController::class, 'storeCoupon'])->name('coupons.store');
    Route::post('/coupons/{coupon}/toggle', [Admin\PlanController::class, 'toggleCoupon'])->name('coupons.toggle');
    Route::delete('/coupons/{coupon}', [Admin\PlanController::class, 'destroyCoupon'])->name('coupons.destroy');
    Route::get('/taxes', [Admin\PlanController::class, 'taxes'])->name('taxes.index');
    Route::post('/taxes', [Admin\PlanController::class, 'storeTax'])->name('taxes.store');
    Route::post('/taxes/{tax}/toggle', [Admin\PlanController::class, 'toggleTax'])->name('taxes.toggle');
    Route::delete('/taxes/{tax}', [Admin\PlanController::class, 'destroyTax'])->name('taxes.destroy');

    // Content
    Route::get('/content/pages', [Admin\ContentController::class, 'pages'])->name('content.pages');
    Route::get('/content/pages/create', [Admin\ContentController::class, 'editPage'])->name('content.pages.create');
    Route::post('/content/pages/create', [Admin\ContentController::class, 'savePage']);
    Route::get('/content/pages/{page}', [Admin\ContentController::class, 'editPage'])->name('content.pages.edit');
    Route::post('/content/pages/{page}', [Admin\ContentController::class, 'savePage']);
    Route::delete('/content/pages/{page}', [Admin\ContentController::class, 'destroyPage'])->name('content.pages.destroy');
    Route::get('/content/posts', [Admin\ContentController::class, 'posts'])->name('content.posts');
    Route::get('/content/posts/create', [Admin\ContentController::class, 'editPost'])->name('content.posts.create');
    Route::post('/content/posts/create', [Admin\ContentController::class, 'savePost']);
    Route::get('/content/posts/{post}', [Admin\ContentController::class, 'editPost'])->name('content.posts.edit');
    Route::post('/content/posts/{post}', [Admin\ContentController::class, 'savePost']);
    Route::delete('/content/posts/{post}', [Admin\ContentController::class, 'destroyPost'])->name('content.posts.destroy');
    Route::get('/content/faqs', [Admin\ContentController::class, 'faqs'])->name('content.faqs');
    Route::post('/content/faqs', [Admin\ContentController::class, 'saveFaq'])->name('content.faqs.store');
    Route::post('/content/faqs/{faq}', [Admin\ContentController::class, 'saveFaq'])->name('content.faqs.update');
    Route::delete('/content/faqs/{faq}', [Admin\ContentController::class, 'destroyFaq'])->name('content.faqs.destroy');

    // Languages / translations
    Route::get('/languages', [Admin\LanguageController::class, 'index'])->name('languages.index');
    Route::post('/languages', [Admin\LanguageController::class, 'store'])->name('languages.store');
    Route::get('/languages/{language}', [Admin\LanguageController::class, 'edit'])->name('languages.edit');
    Route::put('/languages/{language}', [Admin\LanguageController::class, 'update'])->name('languages.update');
    Route::post('/languages/{language}/toggle', [Admin\LanguageController::class, 'toggle'])->name('languages.toggle');
    Route::post('/languages/{language}/default', [Admin\LanguageController::class, 'setDefault'])->name('languages.default');
    Route::delete('/languages/{language}', [Admin\LanguageController::class, 'destroy'])->name('languages.destroy');

    // Reports + audit
    Route::get('/reports', [Admin\ReportController::class, 'index'])->name('reports.index');
    Route::get('/audit-log', [Admin\ReportController::class, 'auditLog'])->name('audit.index');

    // Addons + backup
    Route::get('/addons', [Admin\AddonController::class, 'index'])->name('addons.index');
    Route::post('/addons/{slug}/toggle', [Admin\AddonController::class, 'toggle'])->name('addons.toggle');
    Route::get('/backup/download', [Admin\BackupController::class, 'download'])->name('backup.download');

    // One-click self-updater (pulls latest from GitHub and applies it)
    Route::get('/updates', [Admin\SelfUpdateController::class, 'index'])->name('updates');
    Route::post('/updates/source', [Admin\SelfUpdateController::class, 'saveSource'])->name('updates.source');
    Route::post('/updates/clear-token', [Admin\SelfUpdateController::class, 'clearToken'])->name('updates.clear-token');
    Route::get('/updates/check', [Admin\SelfUpdateController::class, 'check'])->name('updates.check');
    Route::post('/updates/run', [Admin\SelfUpdateController::class, 'run'])->name('updates.run');
    // Advanced: per-file GitHub editor
    Route::get('/updates/editor', [Admin\UpdatePanelController::class, 'index'])->name('updates.editor');

    // SSO providers
    Route::get('/sso', [Admin\SsoController::class, 'index'])->name('sso.index');
    Route::post('/sso', [Admin\SsoController::class, 'store'])->name('sso.store');
    Route::put('/sso/{provider}', [Admin\SsoController::class, 'update'])->name('sso.update');
    Route::post('/sso/{provider}/toggle', [Admin\SsoController::class, 'toggle'])->name('sso.toggle');
    Route::delete('/sso/{provider}', [Admin\SsoController::class, 'destroy'])->name('sso.destroy');
    Route::post('/users/{user}/credits', [Admin\UserController::class, 'adjustCredits'])->name('users.credits');

    // Settings
    Route::post('/settings/test-email', [Admin\SettingsController::class, 'sendTestEmail'])->name('settings.test-email');
    Route::post('/settings/telegram-webhook', [Admin\SettingsController::class, 'setTelegramWebhook'])->name('settings.telegram-webhook');
    Route::post('/settings/branding', [Admin\SettingsController::class, 'uploadBranding'])->name('settings.branding');
    Route::put('/settings/payments', [Admin\SettingsController::class, 'updatePayments'])->name('settings.payments');
    Route::put('/settings/{tab}', [Admin\SettingsController::class, 'update'])->name('settings.update');
    Route::get('/settings/{tab?}', [Admin\SettingsController::class, 'index'])->name('settings');
});

/*
|--------------------------------------------------------------------------
| Growth & traffic — authenticated (batch #6)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'not.suspended'])->group(function () {
    // Gamification
    Route::get('/badges', [User\BadgeController::class, 'index'])->name('badges.index');
    // Web-push (VAPID) browser notifications
    Route::get('/me/push/key', [User\PushController::class, 'vapidKey'])->name('push.key');
    Route::post('/me/push/subscribe', [User\PushController::class, 'subscribe'])->name('push.subscribe');
    Route::post('/me/push/unsubscribe', [User\PushController::class, 'unsubscribe'])->name('push.unsubscribe');
    // AI helpers (bio/title generation)
    Route::post('/me/ai/bio', [User\AiController::class, 'bio'])->middleware('throttle:20,1')->name('ai.bio');
    Route::post('/me/ai/title', [User\AiController::class, 'title'])->middleware('throttle:20,1')->name('ai.title');
});

/*
|--------------------------------------------------------------------------
| Growth — admin (batch #6)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/waitlist', [Admin\WaitlistController::class, 'index'])->name('waitlist');
});

/*
|--------------------------------------------------------------------------
| Bio pages + public stats + short link catch-all (keep these LAST)
|--------------------------------------------------------------------------
*/
Route::get('/@{username}', [BioPublicController::class, 'show'])->name('bio.show');
Route::post('/bio/{bioPage}/subscribe', [BioPublicController::class, 'subscribe'])->middleware('throttle:10,1')->name('bio.subscribe');
Route::get('/bio/{bioPage}/vcard.vcf', [BioPublicController::class, 'vcard'])->name('bio.vcard');
Route::get('/b/{block}', [BioPublicController::class, 'blockClick'])->name('bio.block.click');
Route::match(['get', 'post'], '/bio/{bioPage}/tip', [BioPublicController::class, 'tip'])->middleware('throttle:20,1')->name('bio.tip');

Route::get('/stats/{alias}', [User\StatsController::class, 'publicStats'])->name('stats.public');

Route::post('/{alias}/unlock', [RedirectController::class, 'unlock'])->where('alias', '[a-zA-Z0-9\-_]+')->name('redirect.unlock');
Route::get('/{alias}', RedirectController::class)->where('alias', '[a-zA-Z0-9\-_]+')->name('redirect');
