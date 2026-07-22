<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SaaS billing tables: plans, subscriptions, payments, coupons, taxes, affiliates.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 12, 2)->default(0);
            $table->decimal('price_yearly', 12, 2)->default(0);
            $table->decimal('price_lifetime', 12, 2)->default(0);
            $table->unsignedInteger('trial_days')->default(0);
            $table->json('limits');    // links, clicks_per_month, spaces, domains, pixels, team_members, qr_codes, bio_pages, api_rate, retention_days (-1 = unlimited)
            $table->json('features');  // feature flags map: custom_domains, password, expiration, targeting, rotator, deep_links, cloaking, og, utm, api, export, team, bio, qr_logo, remove_branding, no_ads...
            $table->boolean('is_free')->default(false);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->foreignId('plan_id')->index();
            $table->string('cycle', 20); // monthly | yearly | lifetime
            $table->string('status', 20)->default('active')->index(); // active | cancelled | expired | past_due
            $table->string('gateway', 40)->nullable();
            $table->string('gateway_id')->nullable(); // gateway subscription reference
            $table->boolean('auto_renew')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable()->index();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedTinyInteger('dunning_attempts')->default(0);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->foreignId('plan_id')->nullable();
            $table->foreignId('subscription_id')->nullable();
            $table->string('cycle', 20)->nullable();
            $table->string('gateway', 40);
            $table->string('gateway_reference')->nullable()->index();
            $table->string('invoice_number')->nullable()->unique();
            $table->decimal('amount', 12, 2);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->string('currency', 8)->default('USD');
            $table->string('status', 20)->default('pending')->index(); // pending | completed | failed | refunded | declined
            $table->foreignId('coupon_id')->nullable();
            $table->json('tax_details')->nullable();
            $table->json('meta')->nullable(); // gateway payloads, manual payment proof, UPI ref etc.
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('type', 10); // percent | fixed
            $table->decimal('value', 12, 2);
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->json('plan_ids')->nullable(); // null = all plans
            $table->timestamp('expires_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name');           // e.g. "GST 18%"
            $table->string('country', 2)->nullable()->index(); // null = everywhere
            $table->decimal('rate', 5, 2);    // percent
            $table->string('tax_id_label')->nullable(); // e.g. GSTIN, VAT ID
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();      // affiliate earning the commission
            $table->foreignId('referred_user_id');
            $table->foreignId('payment_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default('pending')->index(); // pending | approved | paid | rejected
            $table->timestamps();
        });

        Schema::create('payout_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->decimal('amount', 12, 2);
            $table->string('method', 40);   // paypal | bank | upi
            $table->text('details');        // payout destination details
            $table->string('status', 20)->default('pending')->index(); // pending | paid | rejected
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_requests');
        Schema::dropIfExists('commissions');
        Schema::dropIfExists('tax_rates');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
    }
};
