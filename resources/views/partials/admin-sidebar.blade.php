<div class="p-4">
    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 px-2 py-3">
        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-900 dark:bg-white text-white dark:text-slate-900">
            <x-icon name="shield" class="h-5 w-5"/>
        </span>
        <span class="text-lg font-bold tracking-tight">{{ __('Admin') }}</span>
    </a>

    <nav class="space-y-0.5 mt-2" aria-label="{{ __('Admin navigation') }}">
        @foreach($adminNav as $item)
            <a href="{{ route($item['route']) }}"
               class="nav-item {{ request()->routeIs($item['match']) ? 'nav-item-active' : '' }}">
                <x-icon :name="$item['icon']" class="h-5 w-5 shrink-0"/>
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    <a href="{{ route('admin.backup.download') }}" class="btn-secondary btn-sm w-full mt-4">
        <x-icon name="download" class="h-4 w-4"/> {{ __('Download backup') }}
    </a>
</div>
