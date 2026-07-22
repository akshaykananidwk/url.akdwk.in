<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link-in-bio builder tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bio_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->foreignId('domain_id')->nullable();
            $table->string('username', 60)->unique();
            $table->string('title');
            $table->text('bio')->nullable();
            $table->string('avatar')->nullable();
            $table->string('cover')->nullable();
            $table->string('theme', 40)->default('default');
            $table->json('colors')->nullable(); // {bg, card, text, button, button_text, gradient}
            $table->string('font', 60)->default('Inter');
            $table->json('seo')->nullable();    // {title, description, image, noindex}
            $table->json('socials')->nullable(); // {twitter: url, instagram: url, ...}
            $table->boolean('active')->default(true);
            $table->unsignedBigInteger('views')->default(0);
            $table->timestamps();
        });

        Schema::create('bio_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bio_page_id')->index();
            $table->string('type', 30); // link | text | image | video | email_form | whatsapp | phone | vcard | socials | divider | heading
            $table->json('content');    // per-type payload
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamp('starts_at')->nullable(); // block scheduling
            $table->timestamp('ends_at')->nullable();
            $table->unsignedBigInteger('clicks')->default(0);
            $table->timestamps();
        });

        Schema::create('bio_subscribers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bio_page_id')->index();
            $table->foreignId('bio_block_id')->nullable();
            $table->string('email');
            $table->timestamps();
            $table->unique(['bio_page_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bio_subscribers');
        Schema::dropIfExists('bio_blocks');
        Schema::dropIfExists('bio_pages');
    }
};
