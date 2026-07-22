@extends('layouts.admin')

@section('title', __('Addons') . ' — ' . site_name())
@section('page-title', __('Addons'))

@section('content')
<div class="space-y-5">

    @if(count($addons))
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($addons as $a)
                <div class="card card-pad flex flex-col">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <h2 class="font-semibold truncate">{{ $a['model']->name }}</h2>
                            <p class="text-xs text-slate-500">v{{ $a['model']->version }}</p>
                        </div>
                        @if($a['model']->enabled)<span class="badge-green shrink-0">{{ __('Enabled') }}</span>
                        @else<span class="badge-gray shrink-0">{{ __('Disabled') }}</span>@endif
                    </div>
                    <p class="mt-2 text-sm text-slate-500 flex-1">{{ $a['manifest']['description'] ?? '' }}</p>
                    <form method="POST" action="{{ route('admin.addons.toggle', $a['model']->slug) }}" class="mt-4">
                        @csrf
                        <button class="{{ $a['model']->enabled ? 'btn-secondary' : 'btn-primary' }} btn-sm w-full justify-center">
                            {{ $a['model']->enabled ? __('Disable') : __('Enable') }}
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    @else
        <x-empty-state icon="bolt" :title="__('No addons installed')" :description="__('Drop addon folders into the addons directory to see them here.')"/>
    @endif

    @if(!empty($settingsPages))
        <div class="card card-pad">
            <h2 class="font-semibold mb-3">{{ __('Addon settings') }}</h2>
            <div class="flex flex-wrap gap-2">
                @foreach($settingsPages as $page)
                    <a href="{{ $page['url'] ?? '#' }}" class="btn-secondary btn-sm">{{ $page['label'] ?? ($page['title'] ?? __('Settings')) }}</a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- How to install --}}
    <div class="card card-pad">
        <h2 class="font-semibold mb-2">{{ __('Installing addons') }}</h2>
        <p class="text-sm text-slate-500 mb-3">{{ __('Drop addon folders into:') }}</p>
        <pre class="rounded-xl bg-slate-100 dark:bg-slate-800 p-3 text-xs overflow-x-auto"><code>{{ $addonsPath }}</code></pre>
        <p class="text-sm text-slate-500 mt-4 mb-2">{{ __('Each addon folder must contain an addon.json manifest:') }}</p>
        <pre class="rounded-xl bg-slate-100 dark:bg-slate-800 p-3 text-xs overflow-x-auto"><code>{
    "name": "My addon",
    "version": "1.0.0",
    "description": "What it does",
    "entry": "addon.php"
}</code></pre>
        <p class="help mt-3">{{ __('The entry file is loaded on every request when the addon is enabled and can register hooks via hook_action() / hook_filter().') }}</p>
    </div>
</div>
@endsection
