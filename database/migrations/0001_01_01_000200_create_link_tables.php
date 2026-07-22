<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link platform tables: spaces, domains, pixels, links, clicks, rollups, QR codes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->string('name');
            $table->string('color', 20)->default('#6366f1');
            $table->string('icon', 40)->default('folder');
            $table->timestamps();
        });

        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->index(); // null = global (admin-added, available to everyone)
            $table->string('domain')->unique();
            $table->string('index_redirect')->nullable();      // where the domain root goes
            $table->string('not_found_redirect')->nullable();  // 404 fallback URL
            $table->boolean('is_default')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->string('verification_token', 64)->nullable();
            $table->boolean('ssl')->default(false);
            $table->timestamps();
        });

        Schema::create('pixels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->string('name');
            $table->string('type', 40); // gads | ga4 | gtm | meta | bing | twitter | pinterest | linkedin | quora | snapchat | tiktok | reddit | adroll | custom
            $table->text('value');      // pixel id, or raw HTML/JS for custom
            $table->timestamps();
        });

        Schema::create('links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->foreignId('space_id')->nullable()->index();
            $table->foreignId('domain_id')->nullable()->index();
            $table->string('alias', 100);
            $table->text('destination');
            $table->string('title')->nullable();
            $table->string('type', 20)->default('link'); // link | file | vcard | whatsapp
            $table->string('password')->nullable();       // bcrypt hash
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('max_clicks')->nullable();
            $table->text('expired_redirect')->nullable();
            $table->boolean('disabled')->default(false);
            $table->boolean('cloaking')->default(false);
            $table->json('deep_link')->nullable();   // {enabled, ios, android}
            $table->json('og')->nullable();          // {title, description, image}
            $table->json('utm')->nullable();         // {source, medium, campaign, term, content}
            $table->json('targeting')->nullable();   // {country:[{key,url}], platform:[], language:[], device:[], time:[{days,from,to,url}], rotation:[{url,weight}]}
            $table->json('meta')->nullable();        // file path, vcard fields, wa number etc.
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();
            $table->boolean('public_stats')->default(false);
            $table->unsignedBigInteger('clicks_count')->default(0);
            $table->unsignedBigInteger('unique_clicks_count')->default(0);
            $table->unsignedBigInteger('qr_scans_count')->default(0);
            $table->timestamp('last_click_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->unique(['domain_id', 'alias']);
            $table->index('alias');
            $table->index('created_at');
        });

        Schema::create('link_pixel', function (Blueprint $table) {
            $table->foreignId('link_id')->index();
            $table->foreignId('pixel_id')->index();
            $table->primary(['link_id', 'pixel_id']);
        });

        // Raw click events. High volume: aggregated daily into click_rollups then pruned per plan retention.
        Schema::create('clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('link_id')->index();
            $table->foreignId('user_id')->index(); // link owner, denormalized for per-user quota queries
            $table->string('ip_hash', 64)->nullable();
            $table->string('country', 2)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('language', 10)->nullable();
            $table->string('os', 40)->nullable();
            $table->string('browser', 40)->nullable();
            $table->string('device', 20)->nullable(); // desktop | mobile | tablet
            $table->string('referer_host', 190)->nullable();
            $table->text('referer_url')->nullable();
            $table->string('isp', 190)->nullable();
            $table->boolean('is_unique')->default(false);
            $table->boolean('is_qr')->default(false);
            $table->timestamp('created_at')->index();
            $table->index(['link_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        // Daily aggregates per link — powers charts at any scale.
        Schema::create('click_rollups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('link_id')->index();
            $table->foreignId('user_id')->index();
            $table->date('date');
            $table->unsignedBigInteger('clicks')->default(0);
            $table->unsignedBigInteger('uniques')->default(0);
            $table->unsignedBigInteger('qr_scans')->default(0);
            $table->json('breakdown')->nullable(); // {country:{IN:5}, os:{}, browser:{}, device:{}, referer:{}, language:{}, city:{}, region:{}, hour:{0..23}}
            $table->timestamps();
            $table->unique(['link_id', 'date']);
            $table->index(['user_id', 'date']);
        });

        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->foreignId('link_id')->nullable()->index();
            $table->string('name');
            $table->json('options'); // {fg, bg, logo, frame_text, ec_level, size, style}
            $table->unsignedBigInteger('scans')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_codes');
        Schema::dropIfExists('click_rollups');
        Schema::dropIfExists('clicks');
        Schema::dropIfExists('link_pixel');
        Schema::dropIfExists('links');
        Schema::dropIfExists('pixels');
        Schema::dropIfExists('domains');
        Schema::dropIfExists('spaces');
    }
};
