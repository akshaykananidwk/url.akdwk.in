<div class="p-4">
    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 px-2 py-3">
        @if(setting('site_logo'))
            <img src="{{ storage_url(setting('site_logo')) }}" alt="{{ site_name() }}" class="h-8 w-auto">
        @else
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-600 text-white">
                <x-icon name="link" class="h-5 w-5"/>
            </span>
            <span class="text-lg font-bold tracking-tight">{{ site_name() }}</span>
        @endif
    </a>

    <a href="{{ route('links.create') }}" class="btn-primary w-full mt-2 mb-4">
        <x-icon name="plus" class="h-5 w-5"/> {{ __('New link') }}
    </a>

    <nav class="space-y-0.5" aria-label="{{ __('Sidebar') }}">
        @foreach($navItems as $item)
            <a href="{{ route($item['route']) }}"
               class="nav-item {{ request()->routeIs($item['match']) ? 'nav-item-active' : '' }}">
                <x-icon :name="$item['icon']" class="h-5 w-5 shrink-0"/>
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    @php($plan = auth()->user()->currentPlan())
    <div class="mt-6 card card-pad !p-4">
        <div class="flex items-center justify-between text-sm">
            <span class="font-semibold">{{ $plan->name }}</span>
            @if(auth()->user()->onTrial())
                <span class="badge-amber">{{ __('Trial') }}</span>
            @elseif($plan->is_free)
                <span class="badge-gray">{{ __('Free') }}</span>
            @else
                <span class="badge-green">{{ __('Active') }}</span>
            @endif
        </div>
        @if($plan->is_free)
            <a href="{{ route('billing.plans') }}" class="btn-primary btn-sm w-full mt-3">{{ __('Upgrade') }}</a>
        @else
            <a href="{{ route('billing.invoices') }}" class="btn-secondary btn-sm w-full mt-3">{{ __('Manage plan') }}</a>
        @endif
    </div>
</div>
