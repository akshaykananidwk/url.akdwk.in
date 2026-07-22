@props(['name', 'title' => ''])
{{-- Alpine modal: open with $dispatch('open-modal', 'name') --}}
<div x-data="{ open: false }"
     @open-modal.window="if ($event.detail === '{{ $name }}') open = true"
     @close-modal.window="open = false"
     @keydown.escape.window="open = false"
     x-cloak x-show="open" class="fixed inset-0 z-[70]" role="dialog" aria-modal="true">
    <div x-show="open" x-transition.opacity class="absolute inset-0 bg-slate-900/60" @click="open = false"></div>
    <div class="absolute inset-x-0 bottom-0 sm:inset-0 sm:flex sm:items-center sm:justify-center p-0 sm:p-4">
        <div x-show="open"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-8 sm:translate-y-0 sm:scale-95 opacity-0" x-transition:enter-end="translate-y-0 sm:scale-100 opacity-100"
             class="card w-full sm:max-w-lg max-h-[90vh] overflow-y-auto rounded-b-none rounded-t-2xl sm:rounded-2xl">
            <div class="flex items-center justify-between px-5 pt-4 pb-2">
                <h3 class="font-semibold text-base">{{ $title }}</h3>
                <button @click="open = false" class="btn-ghost btn-sm -me-2" aria-label="{{ __('Close') }}"><x-icon name="x" class="h-5 w-5"/></button>
            </div>
            <div class="px-5 pb-5">{{ $slot }}</div>
        </div>
    </div>
</div>
