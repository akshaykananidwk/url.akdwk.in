@extends('layouts.base')

@php($m = $link->meta ?? [])
@php($fullName = trim(($m['first_name'] ?? '') . ' ' . ($m['last_name'] ?? '')))
@php($initials = mb_strtoupper(mb_substr($m['first_name'] ?? '', 0, 1) . mb_substr($m['last_name'] ?? '', 0, 1)))

@section('title', ($fullName ?: $link->title) . ' — ' . site_name())

@push('head')
    <meta name="robots" content="noindex, nofollow">
    <style>
        .vc-page {
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            padding: 24px 16px; background: #eef2f7;
        }
        .vc-page.dark, html.dark .vc-page { background: #0f172a; }
        .vc-card {
            width: 100%; max-width: 26rem; background: #fff; color: #0f172a;
            border-radius: 22px; padding: 28px 24px 24px; text-align: center;
            box-shadow: 0 18px 50px rgba(15,23,42,.18);
        }
        html.dark .vc-card { background: #1e293b; color: #f8fafc; box-shadow: 0 18px 50px rgba(0,0,0,.5); }
        .vc-avatar {
            width: 88px; height: 88px; margin: 0 auto 14px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 32px; font-weight: 700; color: #fff;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
        }
        .vc-name { font-size: 22px; font-weight: 700; }
        .vc-org { margin-top: 4px; font-size: 14.5px; opacity: .7; }
        .vc-rows { margin-top: 22px; display: flex; flex-direction: column; gap: 4px; text-align: start; }
        .vc-row {
            display: flex; align-items: center; gap: 12px; padding: 12px 8px;
            border-radius: 12px; text-decoration: none; color: inherit; min-height: 52px;
            transition: background .15s ease;
        }
        a.vc-row:hover { background: rgba(99,102,241,.08); }
        .vc-row .ic {
            display: inline-flex; align-items: center; justify-content: center;
            width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;
            background: rgba(99,102,241,.12); color: #6366f1;
        }
        html.dark .vc-row .ic { background: rgba(129,140,248,.18); color: #a5b4fc; }
        .vc-row .ic svg { width: 20px; height: 20px; }
        .vc-row .lbl { font-size: 11px; text-transform: uppercase; letter-spacing: .04em; opacity: .55; }
        .vc-row .val { font-size: 15px; font-weight: 500; word-break: break-word; }
        .vc-save {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            margin-top: 22px; min-height: 52px; padding: 14px 18px; border-radius: 14px;
            background: #6366f1; color: #fff; font-size: 16px; font-weight: 600;
            text-decoration: none; box-shadow: 0 8px 20px rgba(99,102,241,.35);
        }
        .vc-save:active { transform: scale(.98); }
        .vc-save svg { width: 20px; height: 20px; }
        .vc-share { margin-top: 12px; }
        .vc-share button {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%; min-height: 48px; padding: 12px 16px; border-radius: 14px;
            border: 1px solid rgba(99,102,241,.35); background: transparent; color: inherit;
            font-size: 15px; font-weight: 600; cursor: pointer; font-family: inherit;
        }
        .vc-share button svg { width: 18px; height: 18px; }
        .vc-footer { margin-top: 24px; text-align: center; font-size: 12px; opacity: .5; }
        .vc-footer a { color: inherit; text-decoration: none; }
    </style>
@endpush

@section('body')
<div class="vc-page">
    <div class="vc-card">
        <div class="vc-avatar" aria-hidden="true">{{ $initials ?: '👤' }}</div>
        <h1 class="vc-name">{{ $fullName ?: $link->title }}</h1>
        @if(!empty($m['organization']))
            <p class="vc-org">{{ $m['organization'] }}</p>
        @endif

        <div class="vc-rows">
            @if(!empty($m['phone']))
                <a class="vc-row" href="tel:{{ preg_replace('/[^0-9+]/', '', $m['phone']) }}">
                    <span class="ic"><x-icon name="phone"/></span>
                    <span><span class="lbl">{{ __('Phone') }}</span><br><span class="val">{{ $m['phone'] }}</span></span>
                </a>
            @endif
            @if(!empty($m['email']))
                <a class="vc-row" href="mailto:{{ $m['email'] }}">
                    <span class="ic"><x-icon name="mail"/></span>
                    <span><span class="lbl">{{ __('Email') }}</span><br><span class="val">{{ $m['email'] }}</span></span>
                </a>
            @endif
            @if(!empty($m['website']))
                @php($site = \Illuminate\Support\Str::startsWith($m['website'], ['http://', 'https://']) ? $m['website'] : 'https://' . $m['website'])
                <a class="vc-row" href="{{ $site }}" target="_blank" rel="noopener">
                    <span class="ic"><x-icon name="external"/></span>
                    <span><span class="lbl">{{ __('Website') }}</span><br><span class="val">{{ $m['website'] }}</span></span>
                </a>
            @endif
            @if(!empty($m['address']))
                <div class="vc-row">
                    <span class="ic"><x-icon name="home"/></span>
                    <span><span class="lbl">{{ __('Address') }}</span><br><span class="val">{{ $m['address'] }}</span></span>
                </div>
            @endif
        </div>

        <a class="vc-save" href="{{ route('redirect', $link->alias) }}?vcf=1">
            <x-icon name="download"/> {{ __('Save contact') }}
        </a>

        <div class="vc-share">
            <button type="button" onclick="copyText(@js($link->shortUrl()))">
                <x-icon name="copy"/> {{ __('Copy link') }}
            </button>
        </div>

        <footer class="vc-footer">
            <a href="{{ url('/') }}" rel="noopener">{{ __('Powered by :site', ['site' => site_name()]) }}</a>
        </footer>
    </div>
</div>
@endsection
