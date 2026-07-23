<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Feature batch #1: UTM templates, alert channels, link health checks.
 * Ships as a dated migration so existing installs pick it up via the updater.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utm_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->string('name');
            $table->string('source')->nullable();
            $table->string('medium')->nullable();
            $table->string('campaign')->nullable();
            $table->string('term')->nullable();
            $table->string('content')->nullable();
            $table->timestamps();
        });

        Schema::create('alert_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->string('type', 20);        // slack | discord | telegram
            $table->string('target', 500);     // incoming-webhook URL, or "botToken|chatId" for telegram
            $table->boolean('instant')->default(false);          // ping on every click
            $table->unsignedInteger('milestone')->default(0);    // ping every N clicks (0 = off)
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::table('links', function (Blueprint $table) {
            $table->string('health_status', 20)->nullable()->after('archived_at'); // ok | broken | unknown
            $table->timestamp('health_checked_at')->nullable()->after('health_status');
            $table->unsignedSmallInteger('health_code')->nullable()->after('health_checked_at');
        });
    }

    public function down(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->dropColumn(['health_status', 'health_checked_at', 'health_code']);
        });
        Schema::dropIfExists('alert_channels');
        Schema::dropIfExists('utm_templates');
    }
};
