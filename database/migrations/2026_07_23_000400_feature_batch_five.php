<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Feature batch #5 (enterprise): SSO/OIDC, white-label workspaces, credits,
 * and a per-user activity feed.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Credits balance on users (pay-per-use pool, separate from plan quota).
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('credits')->default(0)->after('affiliate_balance');
            $table->json('branding')->nullable()->after('credits'); // white-label: logo, name, colors
        });

        // Credit ledger — every grant/spend is recorded.
        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->integer('amount');          // + granted, - spent
            $table->string('reason', 100);
            $table->unsignedBigInteger('balance_after');
            $table->timestamp('created_at')->nullable();
        });

        // Activity feed — user + workspace actions.
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();     // whose feed / actor
            $table->foreignId('workspace_id')->nullable()->index(); // owner of the workspace, for team feeds
            $table->string('action', 60);
            $table->string('subject')->nullable();     // human summary
            $table->json('meta')->nullable();
            $table->string('ip', 64)->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });

        // SSO / OIDC providers (per install, admin-managed).
        Schema::create('sso_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type', 20)->default('oidc'); // oidc
            $table->string('client_id');
            $table->text('client_secret');
            $table->string('issuer')->nullable();          // OIDC discovery base
            $table->string('authorize_url')->nullable();
            $table->string('token_url')->nullable();
            $table->string('userinfo_url')->nullable();
            $table->string('scopes')->default('openid email profile');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sso_providers');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('credit_transactions');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['credits', 'branding']);
        });
    }
};
