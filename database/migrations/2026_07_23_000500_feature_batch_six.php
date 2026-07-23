<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Feature batch #6 — growth & traffic engine.
 *
 * Adds the storage for web-push subscriptions, the gamification badge
 * ledger, a feature waitlist, and the per-user activity/streak columns the
 * weekly digest and badge engine read from.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Web-push (VAPID) browser subscriptions.
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('endpoint');
            $table->char('endpoint_hash', 64);  // sha256(endpoint) — indexable stand-in for the long URL
            $table->string('public_key');       // p256dh
            $table->string('auth_token');        // auth
            $table->string('content_encoding')->default('aesgcm');
            $table->timestamps();
            $table->unique(['user_id', 'endpoint_hash'], 'push_sub_unique');
        });

        // Gamification — one row per badge a user has earned.
        Schema::create('user_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('badge_key', 60);
            $table->json('meta')->nullable();
            $table->timestamp('awarded_at')->useCurrent();
            $table->unique(['user_id', 'badge_key']);
        });

        // "Notify me" waitlist for gated/upcoming features.
        Schema::create('waitlist_entries', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('feature', 60)->default('general');
            $table->string('ip', 45)->nullable();
            $table->timestamps();
            $table->index(['feature', 'created_at']);
        });

        // Streak / activity / digest bookkeeping on the user.
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('streak_days')->default(0)->after('affiliate_balance');
            $table->date('last_active_on')->nullable()->after('streak_days');
            $table->timestamp('last_digest_at')->nullable()->after('last_active_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
        Schema::dropIfExists('user_badges');
        Schema::dropIfExists('waitlist_entries');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['streak_days', 'last_active_on', 'last_digest_at']);
        });
    }
};
