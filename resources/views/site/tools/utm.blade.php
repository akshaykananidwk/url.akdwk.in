@extends('layouts.landing')

@section('title', __('UTM Campaign URL Builder') . ' — ' . site_name())

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-10" x-data="utmBuilder()">

    <div class="max-w-2xl">
        <span class="badge-brand mb-4">{{ __('Free tool') }}</span>
        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">{{ __('UTM Campaign URL Builder') }}</h1>
        <p class="mt-4 text-slate-500 dark:text-slate-400">
            {{ __('Add UTM parameters to any link so Google Analytics and other tools can tell you exactly which campaign, channel and ad drove each visit. Fill in the fields and copy your tagged URL — everything happens in your browser.') }}
        </p>
    </div>

    <div class="mt-8 grid lg:grid-cols-2 gap-6 items-start">
        <div class="card card-pad space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1.5">{{ __('Website URL') }} <span class="text-rose-500">*</span></label>
                <input type="url" x-model="base" inputmode="url" placeholder="https://example.com/page" class="input w-full">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1.5">{{ __('Campaign source') }} <span class="text-rose-500">*</span></label>
                <input type="text" x-model="source" placeholder="google, newsletter, facebook" class="input w-full">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1.5">{{ __('Campaign medium') }} <span class="text-rose-500">*</span></label>
                <input type="text" x-model="medium" placeholder="cpc, email, social" class="input w-full">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1.5">{{ __('Campaign name') }} <span class="text-rose-500">*</span></label>
                <input type="text" x-model="campaign" placeholder="spring_sale" class="input w-full">
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1.5">{{ __('Campaign term') }}</label>
                    <input type="text" x-model="term" placeholder="running+shoes" class="input w-full">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">{{ __('Campaign content') }}</label>
                    <input type="text" x-model="content" placeholder="logolink" class="input w-full">
                </div>
            </div>
        </div>

        <div class="card card-pad">
            <h2 class="text-sm font-medium">{{ __('Your tagged URL') }}</h2>
            <div class="mt-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 p-4 text-sm break-all min-h-[4rem] font-mono text-brand-600 dark:text-brand-400"
                 x-text="result || @js(__('Fill in the fields to build your URL…'))"></div>
            <div class="mt-4 flex flex-wrap gap-2">
                <button type="button" class="btn-primary" :disabled="!result" @click="copy()">
                    <x-icon name="copy" class="h-4 w-4"/>
                    <span x-text="copied ? @js(__('Copied!')) : @js(__('Copy URL'))"></span>
                </button>
                <a class="btn-secondary" :class="!result && 'pointer-events-none opacity-50'" :href="result || '#'" target="_blank" rel="noopener">
                    <x-icon name="external" class="h-4 w-4"/> {{ __('Test link') }}
                </a>
            </div>
            <p class="mt-4 text-xs text-slate-400">{{ __('Tip: keep names lowercase and consistent so your reports stay tidy.') }}</p>
        </div>
    </div>

    <div class="mt-10 card card-pad text-center bg-gradient-to-br from-brand-50 to-white dark:from-brand-950/40 dark:to-slate-900">
        <h2 class="text-lg font-bold">{{ __('Track every campaign automatically') }}</h2>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 max-w-lg mx-auto">{{ __('Attach UTM tags to your short links and watch clicks, sources and conversions roll in — no spreadsheets required.') }}</p>
        <a href="{{ route('register') }}" class="btn-primary mt-4">{{ __('Start free') }}</a>
    </div>

</div>

<script>
    function utmBuilder() {
        return {
            base: '', source: '', medium: '', campaign: '', term: '', content: '', copied: false,
            get result() {
                if (!this.base) return '';
                let url;
                try { url = new URL(this.base); } catch (e) { return ''; }
                const params = {
                    utm_source: this.source, utm_medium: this.medium, utm_campaign: this.campaign,
                    utm_term: this.term, utm_content: this.content,
                };
                for (const [k, v] of Object.entries(params)) {
                    const val = (v || '').trim();
                    if (val) url.searchParams.set(k, val); else url.searchParams.delete(k);
                }
                return url.toString();
            },
            async copy() {
                if (!this.result) return;
                try {
                    await navigator.clipboard.writeText(this.result);
                    this.copied = true;
                    setTimeout(() => this.copied = false, 1500);
                } catch (e) {}
            },
        };
    }
</script>
@endsection
