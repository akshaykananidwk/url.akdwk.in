@extends('layouts.base')

@php($meta = $link->meta ?? [])

@section('title', ($meta['title'] ?? $link->title) . ' — ' . site_name())

@push('head')
    <meta name="robots" content="noindex, nofollow">
    <style>
        .music-page {
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            padding: 24px 16px; background: #0b0b12; color: #f5f5f7;
            background-image: radial-gradient(120% 90% at 50% 0%, #1e1b3a 0%, #0b0b12 60%);
        }
        .music-card { width: 100%; max-width: 28rem; text-align: center; }
        .music-art {
            width: 200px; height: 200px; margin: 0 auto 22px; border-radius: 20px;
            object-fit: cover; display: block; box-shadow: 0 20px 50px rgba(0,0,0,.55);
        }
        .music-art-placeholder {
            width: 200px; height: 200px; margin: 0 auto 22px; border-radius: 20px;
            display: flex; align-items: center; justify-content: center; font-size: 68px;
            background: linear-gradient(135deg, #6366f1, #ec4899); box-shadow: 0 20px 50px rgba(0,0,0,.55);
        }
        .music-title { font-size: 24px; font-weight: 700; line-height: 1.2; }
        .music-artist { margin-top: 6px; font-size: 15px; opacity: .7; }
        .music-list { margin-top: 26px; display: flex; flex-direction: column; gap: 12px; }
        .music-btn {
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
            min-height: 56px; padding: 14px 20px; border-radius: 14px;
            background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.1);
            color: #f5f5f7; text-decoration: none; font-size: 16px; font-weight: 600;
            transition: background .15s ease, transform .12s ease;
        }
        .music-btn:hover { background: rgba(255,255,255,.12); }
        .music-btn:active { transform: scale(.98); }
        .music-btn .play {
            display: inline-flex; align-items: center; justify-content: center;
            width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0;
            background: #fff; color: #0b0b12; font-size: 13px; font-weight: 700;
        }
        .music-footer { margin-top: 30px; text-align: center; font-size: 12px; opacity: .5; }
        .music-footer a { color: inherit; text-decoration: none; }
    </style>
@endpush

@section('body-class', 'bg-[#0b0b12]')

@section('body')
<div class="music-page">
    <div class="music-card">
        @if(!empty($meta['artwork']))
            <img class="music-art" src="{{ $meta['artwork'] }}" alt="{{ $meta['title'] ?? '' }}">
        @else
            <div class="music-art-placeholder" aria-hidden="true">🎵</div>
        @endif

        <h1 class="music-title">{{ $meta['title'] ?? $link->title }}</h1>
        @if(!empty($meta['artist']))
            <p class="music-artist">{{ $meta['artist'] }}</p>
        @endif

        <div class="music-list">
            @foreach(($meta['services'] ?? []) as $s)
                <a class="music-btn" href="{{ $s['url'] }}" target="_blank" rel="noopener">
                    <span>{{ $s['name'] ?? $s['key'] ?? __('Listen') }}</span>
                    <span class="play" aria-hidden="true">▶</span>
                </a>
            @endforeach
        </div>

        <footer class="music-footer">
            <a href="{{ url('/') }}" rel="noopener">{{ __('Powered by :site', ['site' => site_name()]) }}</a>
        </footer>
    </div>
</div>
@endsection
