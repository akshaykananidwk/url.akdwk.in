@props(['action', 'title', 'message' => null, 'button' => null, 'method' => 'POST'])
{{-- Confirmation modal wrapping a form submit; use the slot for the trigger --}}
<span x-data="{ open: false }">
    <span @click="open = true">{{ $slot }}</span>
    <template x-teleport="body">
        <div x-cloak x-show="open" @keydown.escape.window="open = false" class="fixed inset-0 z-[80]" role="dialog" aria-modal="true">
            <div x-show="open" x-transition.opacity class="absolute inset-0 bg-slate-900/60" @click="open = false"></div>
            <div class="absolute inset-x-0 bottom-0 sm:inset-0 sm:flex sm:items-center sm:justify-center p-0 sm:p-4">
                <div x-show="open" x-transition class="card w-full sm:max-w-sm rounded-b-none rounded-t-2xl sm:rounded-2xl card-pad">
                    <h3 class="font-semibold">{{ $title }}</h3>
                    <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">{{ $message ?? __('This action cannot be undone.') }}</p>
                    <div class="mt-4 flex gap-2 justify-end">
                        <button class="btn-secondary" @click="open = false">{{ __('Cancel') }}</button>
                        <form method="POST" action="{{ $action }}">
                            @csrf
                            @if(strtoupper($method) !== 'POST') @method($method) @endif
                            <button type="submit" class="btn-danger">{{ $button ?? __('Delete') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </template>
</span>
