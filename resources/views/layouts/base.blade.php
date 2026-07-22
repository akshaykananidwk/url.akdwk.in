<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ current_language()?->rtl ? 'rtl' : 'ltr' }}"
      class="min-h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', site_name())</title>
    <meta name="description" content="@yield('meta_description', setting('meta_description', setting('tagline', 'Short links, QR codes & analytics')))">
    @stack('meta')
    <link rel="manifest" href="{{ route('manifest') }}">
    <meta name="theme-color" content="#6366f1">
    @if(setting('site_favicon'))
        <link rel="icon" href="{{ storage_url(setting('site_favicon')) }}">
    @else
        <link rel="icon" href="data:image/svg+xml,{{ rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#6366f1"><path d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757 1.591 1.59-1.757 1.758a2.25 2.25 0 1 0 3.182 3.182l4.5-4.5a2.25 2.25 0 0 0 0-3.182 2.4 2.4 0 0 0-.62-.44l1.53-1.53c.16.09.31.19.44.5Z"/><path d="M10.81 15.312a4.5 4.5 0 0 1-1.242-7.244l4.5-4.5a4.5 4.5 0 0 1 6.364 6.364l-1.757 1.757-1.591-1.59 1.757-1.758a2.25 2.25 0 1 0-3.182-3.182l-4.5 4.5a2.25 2.25 0 0 0 .62 3.622l-1.53 1.53a4.5 4.5 0 0 1-.44-.5Z"/></svg>') }}">
    @endif
    {{-- Apply the saved theme before paint to avoid a flash --}}
    <script>
        (function () {
            var t = localStorage.getItem('theme');
            if (t === 'dark' || (t !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        window.__t = { copied: @json(__('Copied to clipboard')) };
    </script>
    @stack('head')
</head>
<body class="min-h-screen @yield('body-class')">
@yield('body')

{{-- Toast notifications --}}
<div x-data="{ toasts: [] }"
     @toast.document="const t = { id: Date.now(), ...$event.detail }; toasts.push(t); setTimeout(() => toasts = toasts.filter(x => x.id !== t.id), 3500)"
     class="fixed z-[90] bottom-20 sm:bottom-6 inset-x-4 sm:inset-x-auto sm:right-6 flex flex-col items-center sm:items-end gap-2 pointer-events-none"
     aria-live="polite">
    <template x-for="t in toasts" :key="t.id">
        <div class="pointer-events-auto flex items-center gap-2.5 rounded-xl px-4 py-3 text-sm font-medium text-white shadow-lg max-w-sm"
             :class="t.type === 'error' ? 'bg-rose-600' : (t.type === 'info' ? 'bg-slate-700' : 'bg-emerald-600')"
             x-transition.opacity.duration.300ms>
            <svg x-show="t.type !== 'error'" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
            <svg x-show="t.type === 'error'" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            <span x-text="t.message"></span>
        </div>
    </template>
</div>

@if(session('status'))
    <script>document.addEventListener('DOMContentLoaded', () => toast(@json(session('status'))));</script>
@endif
@if($errors->any())
    <script>document.addEventListener('DOMContentLoaded', () => toast(@json($errors->first()), 'error'));</script>
@endif
@stack('scripts')
</body>
</html>
