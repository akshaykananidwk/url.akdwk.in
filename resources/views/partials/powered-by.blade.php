{{-- "Powered by" attribution badge for free public pages. Optional $forUser. --}}
@php($forUser = $forUser ?? null)
@unless($forUser && $forUser->currentPlan()->hasFeature('remove_branding'))
    <div class="mt-6 text-center">
        <a href="{{ url('/?ref=powered') }}"
           target="_blank"
           rel="noopener"
           class="inline-flex items-center gap-1 text-xs text-slate-400 hover:text-brand-600 transition-colors">
            <span aria-hidden="true">⚡</span>
            <span>{{ __('Powered by :name', ['name' => site_name()]) }}</span>
        </a>
    </div>
@endunless
