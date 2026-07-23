@php
    $themes = [
        'default' => ['bg' => '#eef2f7', 'card' => '#ffffff', 'text' => '#0f172a', 'button' => '#4f46e5', 'button_text' => '#ffffff'],
        'midnight' => ['bg' => '#0f172a', 'card' => '#1e293b', 'text' => '#f8fafc', 'button' => '#6366f1', 'button_text' => '#ffffff'],
        'sunset' => ['bg' => '#431407', 'card' => '#7c2d12', 'text' => '#ffedd5', 'button' => '#fb923c', 'button_text' => '#431407'],
        'forest' => ['bg' => '#052e16', 'card' => '#14532d', 'text' => '#dcfce7', 'button' => '#22c55e', 'button_text' => '#052e16'],
        'ocean' => ['bg' => '#082f49', 'card' => '#0c4a6e', 'text' => '#e0f2fe', 'button' => '#38bdf8', 'button_text' => '#082f49'],
        'candy' => ['bg' => '#fdf2f8', 'card' => '#ffffff', 'text' => '#500724', 'button' => '#ec4899', 'button_text' => '#ffffff'],
        'mono' => ['bg' => '#fafafa', 'card' => '#ffffff', 'text' => '#111111', 'button' => '#111111', 'button_text' => '#ffffff'],
    ];
    $colors = array_merge(
        $themes[$page->theme ?? 'default'] ?? $themes['default'],
        array_filter($page->colors ?? [])
    );
    $font = $page->font ?: 'Inter';
    $seo = $page->seo ?? [];

    $socialIcons = [
        'twitter' => 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z',
        'instagram' => 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12s.014 3.668.072 4.948c.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24s3.668-.014 4.948-.072c4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948s-.014-3.667-.072-4.947c-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z',
        'facebook' => 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z',
        'youtube' => 'M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z',
        'tiktok' => 'M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z',
        'linkedin' => 'M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z',
        'github' => 'M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12',
        'website' => 'M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm7.938 9h-3.02a15.9 15.9 0 0 0-1.51-4.98A8.03 8.03 0 0 1 19.938 9zM12 2.06c.958 1.226 1.98 3.376 2.44 6.94H9.56c.46-3.564 1.482-5.714 2.44-6.94zM4.062 9a8.03 8.03 0 0 1 4.53-4.98A15.9 15.9 0 0 0 7.082 9h-3.02zm-.53 2h3.32a24.9 24.9 0 0 0 0 2h-3.32a8.06 8.06 0 0 1 0-2zm.53 4h3.02a15.9 15.9 0 0 0 1.51 4.98A8.03 8.03 0 0 1 4.062 15zM12 21.94c-.958-1.226-1.98-3.376-2.44-6.94h4.88c-.46 3.564-1.482 5.714-2.44 6.94zM9.4 13a22.5 22.5 0 0 1 0-2h5.2a22.5 22.5 0 0 1 0 2H9.4zm5.008 8.98a15.9 15.9 0 0 0 1.51-4.98h3.02a8.03 8.03 0 0 1-4.53 4.98zM17.148 13a24.9 24.9 0 0 0 0-2h3.32a8.06 8.06 0 0 1 0 2h-3.32z',
    ];
    $socials = array_filter($page->socials ?? []);
@endphp<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $seo['title'] ?? ($page->title . ' — ' . site_name()) }}</title>
    @if(!empty($seo['description']))
        <meta name="description" content="{{ $seo['description'] }}">
    @endif
    @if(!empty($seo['noindex']))
        <meta name="robots" content="noindex, nofollow">
    @endif
    <meta property="og:title" content="{{ $seo['title'] ?? $page->title }}">
    <meta property="og:type" content="profile">
    <meta property="og:url" content="{{ $page->url() }}">
    @if($page->avatar)
        <meta property="og:image" content="{{ storage_url($page->avatar) }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family={{ urlencode($font) }}:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: {{ $colors['bg'] }};
            --card: {{ $colors['card'] }};
            --text: {{ $colors['text'] }};
            --button: {{ $colors['button'] }};
            --button-text: {{ $colors['button_text'] }};
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: '{{ addslashes($font) }}', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--bg); color: var(--text);
            min-height: 100vh; -webkit-tap-highlight-color: transparent;
        }
        .wrap { max-width: 28rem; margin: 0 auto; padding: 0 16px 40px; }
        .cover { width: 100vw; max-width: 100%; height: 160px; object-fit: cover; display: block; border-radius: 0 0 20px 20px; }
        .head { text-align: center; padding-top: 28px; }
        .head.has-cover { margin-top: -48px; padding-top: 0; }
        .avatar {
            width: 96px; height: 96px; border-radius: 50%; object-fit: cover;
            border: 4px solid var(--card); background: var(--card); display: inline-block;
        }
        .avatar-fallback {
            width: 96px; height: 96px; border-radius: 50%; border: 4px solid var(--card);
            background: var(--button); color: var(--button-text);
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 36px; font-weight: 700;
        }
        h1 { font-size: 22px; font-weight: 700; margin-top: 12px; }
        .bio { margin-top: 6px; font-size: 14.5px; opacity: .8; line-height: 1.55; }
        .socials { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin-top: 14px; }
        .socials a {
            display: inline-flex; align-items: center; justify-content: center;
            width: 44px; height: 44px; border-radius: 50%; background: var(--card); color: var(--text);
            text-decoration: none; font-weight: 700; box-shadow: 0 1px 3px rgba(0,0,0,.08);
        }
        .socials svg { width: 20px; height: 20px; fill: currentColor; }
        .blocks { margin-top: 24px; display: flex; flex-direction: column; gap: 12px; }
        .btn-block {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            min-height: 52px; padding: 14px 18px; border-radius: 14px;
            background: var(--button); color: var(--button-text);
            font-size: 15px; font-weight: 600; text-decoration: none; text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,.1);
            transition: transform .12s ease; word-break: break-word;
        }
        .btn-block:active { transform: scale(.98); }
        .btn-outline { background: var(--card); color: var(--text); }
        h2.block-heading { font-size: 17px; font-weight: 700; text-align: center; margin-top: 10px; }
        p.block-text { font-size: 14.5px; line-height: 1.6; opacity: .85; text-align: center; }
        hr.block-divider { border: 0; border-top: 1px solid; opacity: .15; margin: 6px 24px; }
        .block-image { width: 100%; border-radius: 14px; display: block; }
        .video-wrap { position: relative; width: 100%; padding-top: 56.25%; border-radius: 14px; overflow: hidden; background: #000; }
        .video-wrap iframe { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; }
        .email-form { background: var(--card); border-radius: 14px; padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .email-form label { display: block; font-size: 13.5px; font-weight: 600; margin-bottom: 8px; }
        .email-row { display: flex; gap: 8px; }
        .email-row input {
            flex: 1; min-width: 0; min-height: 46px; padding: 10px 12px; font-size: 14px;
            border: 1px solid rgba(128,128,128,.35); border-radius: 10px;
            background: transparent; color: var(--text); font-family: inherit;
        }
        .email-row button {
            min-height: 46px; padding: 0 16px; border: 0; border-radius: 10px;
            background: var(--button); color: var(--button-text);
            font-size: 14px; font-weight: 600; cursor: pointer; font-family: inherit;
        }
        .bio-status {
            margin-top: 10px; font-size: 13.5px; font-weight: 600; color: #16a34a;
        }
        .powered { text-align: center; margin-top: 32px; font-size: 12px; opacity: .55; }
        .powered a { color: inherit; }
    </style>
</head>
<body>
    @if($page->cover)
        <img class="cover" src="{{ storage_url($page->cover) }}" alt="">
    @endif

    <div class="wrap">
        <header class="head {{ $page->cover ? 'has-cover' : '' }}">
            @if($page->avatar)
                <img class="avatar" src="{{ storage_url($page->avatar) }}" alt="{{ $page->title }}">
            @else
                <span class="avatar-fallback">{{ mb_strtoupper(mb_substr($page->title ?: $page->username, 0, 1)) }}</span>
            @endif
            <h1>{{ $page->title }}</h1>
            @if($page->bio)
                <p class="bio">{{ $page->bio }}</p>
            @endif

            @if($socials)
                <div class="socials">
                    @foreach($socials as $network => $value)
                        @php($href = $network === 'website' || str_starts_with((string) $value, 'http') ? $value : 'https://' . ltrim((string) $value, '@/'))
                        <a href="{{ $href }}" target="_blank" rel="noopener" aria-label="{{ ucfirst($network) }}">
                            @if(isset($socialIcons[$network]))
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $socialIcons[$network] }}"/></svg>
                            @else
                                {{ mb_strtoupper(mb_substr($network, 0, 1)) }}
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </header>

        @if(session('bio_status'))
            <p class="bio-status" style="text-align:center">{{ session('bio_status') }}</p>
        @endif

        <main class="blocks">
            @foreach($blocks as $block)
                @php($c = $block->content ?? [])
                @switch($block->type)
                    @case('link')
                        <a class="btn-block" href="{{ route('bio.block.click', $block) }}" target="_blank" rel="noopener">
                            @if(!empty($c['icon']))<span aria-hidden="true">{{ $c['icon'] }}</span>@endif
                            {{ $c['title'] ?? ($c['url'] ?? __('Open link')) }}
                        </a>
                        @break

                    @case('heading')
                        <h2 class="block-heading">{{ $c['text'] ?? '' }}</h2>
                        @break

                    @case('text')
                        <p class="block-text">{{ $c['text'] ?? '' }}</p>
                        @break

                    @case('divider')
                        <hr class="block-divider">
                        @break

                    @case('image')
                        @php($src = $c['url'] ?? (isset($c['path']) ? storage_url($c['path']) : null))
                        @if($src)
                            <img class="block-image" src="{{ $src }}" alt="{{ $c['alt'] ?? '' }}" loading="lazy">
                        @endif
                        @break

                    @case('video')
                        @if(!empty($c['url']))
                            @php($embed = str_replace('watch?v=', 'embed/', $c['url']))
                            @php($embed = str_contains($embed, 'youtu.be/') ? 'https://www.youtube.com/embed/' . ltrim((string) parse_url($embed, PHP_URL_PATH), '/') : $embed)
                            <div class="video-wrap">
                                <iframe src="{{ $embed }}" title="{{ $c['title'] ?? __('Video') }}" loading="lazy"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                        allowfullscreen></iframe>
                            </div>
                        @endif
                        @break

                    @case('email_form')
                        <div class="email-form">
                            <form method="POST" action="{{ route('bio.subscribe', $page) }}">
                                @csrf
                                <input type="hidden" name="block_id" value="{{ $block->id }}">
                                <label for="bio-email-{{ $block->id }}">{{ $c['title'] ?? __('Subscribe to my updates') }}</label>
                                <div class="email-row">
                                    <input id="bio-email-{{ $block->id }}" type="email" name="email" required
                                           placeholder="{{ __('your@email.com') }}" autocomplete="email">
                                    <button type="submit">{{ $c['button'] ?? __('Subscribe') }}</button>
                                </div>
                            </form>
                        </div>
                        @break

                    @case('whatsapp')
                        @if(!empty($c['phone']))
                            <a class="btn-block" href="https://wa.me/{{ preg_replace('/\D+/', '', $c['phone']) }}?text={{ urlencode($c['message'] ?? '') }}" target="_blank" rel="noopener">
                                {{ $c['title'] ?? __('Chat on WhatsApp') }}
                            </a>
                        @endif
                        @break

                    @case('phone')
                        @if(!empty($c['phone']))
                            <a class="btn-block" href="tel:{{ preg_replace('/[^0-9+]/', '', $c['phone']) }}">
                                {{ $c['title'] ?? __('Call me') }}
                            </a>
                        @endif
                        @break

                    @case('vcard')
                        <a class="btn-block" href="{{ route('bio.vcard', $page) }}">
                            {{ $c['title'] ?? __('Save my contact') }}
                        </a>
                        @break

                    @case('socials')
                        <div class="socials">
                            @foreach(array_filter($c) as $network => $value)
                                @php($href = str_starts_with((string) $value, 'http') ? $value : 'https://' . ltrim((string) $value, '@/'))
                                <a href="{{ $href }}" target="_blank" rel="noopener" aria-label="{{ ucfirst($network) }}">
                                    @if(isset($socialIcons[$network]))
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $socialIcons[$network] }}"/></svg>
                                    @else
                                        {{ mb_strtoupper(mb_substr((string) $network, 0, 1)) }}
                                    @endif
                                </a>
                            @endforeach
                        </div>
                        @break

                    @case('music')
                        @if(!empty($c['title']))
                            <h2 class="block-heading">{{ $c['title'] }}</h2>
                        @endif
                        @if(!empty($c['note']))
                            <p class="block-text">{{ $c['note'] }}</p>
                        @endif
                        @break

                    @case('app')
                        @if(!empty($c['ios']) || !empty($c['android']))
                            @if(!empty($c['ios']))
                                <a class="btn-block" href="{{ $c['ios'] }}" target="_blank" rel="noopener">{{ __('Download on the App Store') }}</a>
                            @endif
                            @if(!empty($c['android']))
                                <a class="btn-block" href="{{ $c['android'] }}" target="_blank" rel="noopener">{{ __('Get it on Google Play') }}</a>
                            @endif
                        @elseif(!empty($c['fallback']))
                            <a class="btn-block" href="{{ $c['fallback'] }}" target="_blank" rel="noopener">{{ $c['title'] ?? __('Get the app') }}</a>
                        @endif
                        @break

                    @case('tip')
                        <div class="email-form">
                            <form method="POST" action="{{ route('bio.tip', $page) }}">
                                @csrf
                                <input type="hidden" name="block_id" value="{{ $block->id }}">
                                <label>{{ $c['headline'] ?? __('Support me') }}</label>
                                @if(!empty($c['amounts']) && is_array($c['amounts']))
                                    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:10px">
                                        @foreach(array_filter($c['amounts'], fn ($a) => $a !== '' && $a !== null) as $amt)
                                            <button type="submit" name="amount" value="{{ $amt }}"
                                                    style="flex:1;min-width:72px;min-height:46px;padding:10px 14px;border:0;border-radius:10px;background:var(--button);color:var(--button-text);font-size:14px;font-weight:600;cursor:pointer;font-family:inherit">
                                                {{ format_money($amt, $c['currency'] ?? null) }}
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                                <input type="text" name="name" placeholder="{{ __('Your name (optional)') }}"
                                       style="width:100%;min-height:46px;padding:10px 12px;font-size:14px;border:1px solid rgba(128,128,128,.35);border-radius:10px;background:transparent;color:var(--text);font-family:inherit;margin-bottom:8px">
                                <input type="text" name="message" placeholder="{{ __('Message (optional)') }}"
                                       style="width:100%;min-height:46px;padding:10px 12px;font-size:14px;border:1px solid rgba(128,128,128,.35);border-radius:10px;background:transparent;color:var(--text);font-family:inherit;margin-bottom:8px">
                                <div class="email-row">
                                    <input type="number" name="amount" min="1" step="1" placeholder="{{ __('Custom amount') }}" inputmode="decimal">
                                    <button type="submit">{{ __('Send') }}</button>
                                </div>
                            </form>
                        </div>
                        @break
                @endswitch
            @endforeach
        </main>

        <footer class="powered">
            <a href="{{ url('/') }}" rel="noopener">{{ __('Powered by :site', ['site' => site_name()]) }}</a>
        </footer>
    </div>
</body>
</html>
