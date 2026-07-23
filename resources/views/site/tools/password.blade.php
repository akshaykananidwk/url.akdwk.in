@extends('layouts.landing')

@section('title', __('Password Generator') . ' — ' . site_name())

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-10" x-data="passwordGen()" x-init="generate()">

    <div class="max-w-2xl">
        <span class="badge-brand mb-4">{{ __('Free tool') }}</span>
        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">{{ __('Strong Password Generator') }}</h1>
        <p class="mt-4 text-slate-500 dark:text-slate-400">
            {{ __('Create random, hard-to-guess passwords in one click. Choose the length and character types, and copy your new password. Everything is generated locally with your browser\'s secure random source — nothing is ever sent to a server.') }}
        </p>
    </div>

    <div class="mt-8 grid lg:grid-cols-2 gap-6 items-start">
        <div class="card card-pad space-y-5">
            {{-- Output --}}
            <div>
                <div class="flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-800/60 p-4">
                    <span class="flex-1 min-w-0 break-all font-mono text-base" x-text="password"></span>
                    <button type="button" class="btn-secondary btn-sm shrink-0" @click="regenerate()" aria-label="{{ __('Regenerate') }}">
                        <x-icon name="refresh" class="h-4 w-4"/>
                    </button>
                    <button type="button" class="btn-primary btn-sm shrink-0" @click="copy()">
                        <x-icon name="copy" class="h-4 w-4"/>
                        <span x-text="copied ? @js(__('Copied!')) : @js(__('Copy'))"></span>
                    </button>
                </div>
                {{-- Strength meter --}}
                <div class="mt-3">
                    <div class="h-2 w-full rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-300"
                             :class="strength.color" :style="`width: ${strength.pct}%`"></div>
                    </div>
                    <p class="mt-1.5 text-xs font-medium" :class="strength.text" x-text="strength.label"></p>
                </div>
            </div>

            {{-- Length --}}
            <div>
                <div class="flex items-center justify-between text-sm font-medium mb-1.5">
                    <label for="len">{{ __('Length') }}</label>
                    <span x-text="length" class="badge-brand"></span>
                </div>
                <input id="len" type="range" min="6" max="64" x-model.number="length" @input="generate()" class="w-full accent-brand-600">
            </div>

            {{-- Toggles --}}
            <div class="grid grid-cols-2 gap-3">
                @foreach(['upper' => __('Uppercase (A-Z)'), 'lower' => __('Lowercase (a-z)'), 'digits' => __('Numbers (0-9)'), 'symbols' => __('Symbols (!@#$)')] as $key => $label)
                    <label class="flex items-center gap-2 rounded-xl ring-1 ring-slate-200 dark:ring-slate-800 px-3 py-2.5 cursor-pointer text-sm">
                        <input type="checkbox" x-model="opts.{{ $key }}" @change="generate()" class="accent-brand-600 h-4 w-4">
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="card card-pad space-y-4 text-sm text-slate-500 dark:text-slate-400">
            <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100">{{ __('What makes a password strong?') }}</h2>
            <p>{{ __('Length beats complexity: every extra character multiplies the number of guesses an attacker needs. Aim for at least 16 characters and mix in uppercase, lowercase, numbers and symbols.') }}</p>
            <p>{{ __('Never reuse a password across sites, and store them in a password manager rather than a notebook or browser note. This generator uses crypto.getRandomValues, the same secure randomness used for cryptographic keys.') }}</p>
        </div>
    </div>

    <div class="mt-10 card card-pad text-center bg-gradient-to-br from-brand-50 to-white dark:from-brand-950/40 dark:to-slate-900">
        <h2 class="text-lg font-bold">{{ __('Protect your links too') }}</h2>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 max-w-lg mx-auto">{{ __('Add password protection and expiry dates to any short link — free to try.') }}</p>
        <a href="{{ route('register') }}" class="btn-primary mt-4">{{ __('Get started free') }}</a>
    </div>

</div>

<script>
    function passwordGen() {
        return {
            length: 16,
            opts: { upper: true, lower: true, digits: true, symbols: true },
            password: '',
            copied: false,
            sets: {
                upper: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
                lower: 'abcdefghijklmnopqrstuvwxyz',
                digits: '0123456789',
                symbols: '!@#$%^&*()-_=+[]{};:,.?/',
            },
            generate() {
                let pool = '';
                for (const k in this.opts) if (this.opts[k]) pool += this.sets[k];
                if (!pool) { this.opts.lower = true; pool = this.sets.lower; }
                const n = pool.length;
                const rand = new Uint32Array(this.length);
                crypto.getRandomValues(rand);
                let out = '';
                // Rejection sampling to avoid modulo bias.
                const limit = Math.floor(0xFFFFFFFF / n) * n;
                for (let i = 0; i < this.length; i++) {
                    let r = rand[i];
                    while (r >= limit) { const b = new Uint32Array(1); crypto.getRandomValues(b); r = b[0]; }
                    out += pool[r % n];
                }
                this.password = out;
            },
            regenerate() { this.generate(); },
            get strength() {
                let variety = 0;
                for (const k in this.opts) if (this.opts[k]) variety++;
                const bitsPerChar = { 1: 4.7, 2: 5.7, 3: 5.95, 4: 6.55 }[variety] || 4;
                const entropy = this.length * bitsPerChar;
                if (entropy < 40) return { pct: 25, label: @js(__('Weak')), color: 'bg-rose-500', text: 'text-rose-600 dark:text-rose-400' };
                if (entropy < 70) return { pct: 55, label: @js(__('Fair')), color: 'bg-amber-500', text: 'text-amber-600 dark:text-amber-400' };
                if (entropy < 110) return { pct: 80, label: @js(__('Strong')), color: 'bg-emerald-500', text: 'text-emerald-600 dark:text-emerald-400' };
                return { pct: 100, label: @js(__('Very strong')), color: 'bg-emerald-600', text: 'text-emerald-600 dark:text-emerald-400' };
            },
            async copy() {
                try { await navigator.clipboard.writeText(this.password); this.copied = true; setTimeout(() => this.copied = false, 1500); } catch (e) {}
            },
        };
    }
</script>
@endsection
