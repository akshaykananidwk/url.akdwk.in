@extends('layouts.landing')

@section('title', __('Report abuse') . ' — ' . site_name())

@section('content')
<div class="max-w-xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
    <div class="text-center mb-8">
        <span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 dark:bg-rose-950 dark:text-rose-400">
            <x-icon name="shield" class="h-7 w-7"/>
        </span>
        <h1 class="text-3xl font-extrabold tracking-tight">{{ __('Report abuse') }}</h1>
        <p class="mt-2 text-slate-500 dark:text-slate-400">{{ __('Found a short link pointing to phishing, malware or other harmful content? Let us know — our team reviews every report.') }}</p>
    </div>

    @if(session('status'))
        <div class="mb-5 card !ring-emerald-200 dark:!ring-emerald-900 card-pad !p-4 flex items-center gap-3 text-sm text-emerald-700 dark:text-emerald-300">
            <x-icon name="check" class="h-5 w-5 shrink-0"/>
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('report') }}" class="card card-pad space-y-4">
        @csrf
        <input type="text" name="website" value="" autocomplete="off" tabindex="-1" class="hidden" aria-hidden="true">

        <x-field name="url" :label="__('The short link')" :help="__('Paste the full short URL you want to report.')">
            <input id="url" type="url" name="url" value="{{ old('url') }}" required maxlength="500" class="input" placeholder="{{ url('/') }}/example" inputmode="url">
        </x-field>

        <x-field name="email" :label="__('Your email (optional)')" :help="__('Only used if we need more details about your report.')">
            <input id="email" type="email" name="email" value="{{ old('email') }}" maxlength="190" class="input" autocomplete="email">
        </x-field>

        <x-field name="reason" :label="__('Reason')">
            <select id="reason" name="reason" required class="input">
                <option value="" disabled {{ old('reason') ? '' : 'selected' }}>{{ __('Choose a reason…') }}</option>
                @foreach([
                    'phishing' => __('Phishing'),
                    'malware' => __('Malware'),
                    'spam' => __('Spam'),
                    'scam' => __('Scam / fraud'),
                    'inappropriate' => __('Inappropriate content'),
                    'copyright' => __('Copyright infringement'),
                    'other' => __('Other'),
                ] as $value => $label)
                    <option value="{{ $value }}" @selected(old('reason') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </x-field>

        <x-field name="details" :label="__('Details (optional)')">
            <textarea id="details" name="details" rows="5" maxlength="5000" class="input" placeholder="{{ __('Anything that helps us investigate faster…') }}">{{ old('details') }}</textarea>
        </x-field>

        @include('partials.captcha')

        <button type="submit" class="btn-danger w-full">{{ __('Submit report') }}</button>
    </form>
</div>
@endsection
