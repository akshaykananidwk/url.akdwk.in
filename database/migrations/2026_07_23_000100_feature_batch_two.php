<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Feature batch #2: scheduled links, nested space folders, conversion tracking.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Nested folders: a space can live inside another space.
        Schema::table('spaces', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('user_id')->index();
        });

        // Scheduled activation + conversion counter on links.
        Schema::table('links', function (Blueprint $table) {
            $table->timestamp('starts_at')->nullable()->after('expires_at');
            $table->unsignedBigInteger('conversions_count')->default(0)->after('qr_scans_count');
            $table->string('conversion_goal')->nullable()->after('conversions_count'); // optional label
        });

        // Conversion events (a click that later completed a goal).
        Schema::create('conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('link_id')->index();
            $table->foreignId('user_id')->index();
            $table->decimal('value', 12, 2)->nullable();   // optional revenue
            $table->string('label')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversions');
        Schema::table('links', function (Blueprint $table) {
            $table->dropColumn(['starts_at', 'conversions_count', 'conversion_goal']);
        });
        Schema::table('spaces', function (Blueprint $table) {
            $table->dropColumn('parent_id');
        });
    }
};
