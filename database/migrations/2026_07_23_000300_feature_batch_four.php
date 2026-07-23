<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Feature batch #4: bot / chat integrations.
 * Maps an external chat identity (Telegram chat, Slack user, Discord user) to
 * an app user so messages from that identity create links under their account.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->string('provider', 20);        // telegram | slack | discord
            $table->string('external_id', 100);    // chat id / user id
            $table->string('external_name')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_accounts');
    }
};
