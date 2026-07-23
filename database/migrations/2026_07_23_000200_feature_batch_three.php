<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Feature batch #3: smart app links & music links (link.type reuse via meta),
 * tip jar / vCard bio blocks (no schema change — stored in bio_blocks.content),
 * and a payments column so tip amounts can be recorded.
 *
 * The only new tables are for tip records; smart/music links piggyback on the
 * existing links table via `type` + `meta`, and new bio blocks piggyback on the
 * existing bio_blocks table via `type` + `content`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bio_page_id')->nullable()->index();
            $table->foreignId('user_id')->index();       // page owner (recipient)
            $table->string('supporter_name')->nullable();
            $table->string('supporter_email')->nullable();
            $table->text('message')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 8)->default('USD');
            $table->string('gateway', 40)->nullable();
            $table->string('gateway_reference')->nullable();
            $table->string('status', 20)->default('pending'); // pending | completed | failed
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tips');
    }
};
