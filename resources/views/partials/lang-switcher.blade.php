@if(active_languages()->count() > 1)
    <div x-data="{ open: false }" class="relative">
        <button @click="open = !open" class="btn-ghost btn-sm uppercase" aria-label="{{ __('Change language') }}">
            {{ app()->getLocale() }}
        </button>
        <div x-cloak x-show="open" @click.outside="open = false" x-transition
             class="absolute end-0 mt-2 w-40 card p-1.5 z-50">
            @foreach(active_languages() as $lang)
                <a href="{{ request()->fullUrlWithQuery(['lang' => $lang->code]) }}"
                   class="nav-item {{ app()->getLocale() === $lang->code ? 'nav-item-active' : '' }}">
                    {{ $lang->name }}
                </a>
            @endforeach
        </div>
    </div>
@endif
