{{-- Share buttons. Requires $shareUrl; optional $shareText. --}}
@php($shareText = $shareText ?? '')
<div class="flex flex-wrap items-center gap-2"
     x-data="{ copied: false }">
    @foreach(\App\Services\ShareService::all($shareUrl, $shareText) as $key => $share)
        <a href="{{ $share['href'] }}"
           target="_blank"
           rel="noopener noreferrer"
           class="btn-secondary btn-sm"
           aria-label="{{ __('Share on :channel', ['channel' => $share['label']]) }}">
            <x-icon :name="$share['icon']" class="h-4 w-4"/>
            <span>{{ $share['label'] }}</span>
        </a>
    @endforeach

    <button type="button"
            class="btn-secondary btn-sm"
            @click="navigator.clipboard.writeText(@js($shareUrl)).then(() => { copied = true; setTimeout(() => copied = false, 2000) })">
        <x-icon name="copy" class="h-4 w-4"/>
        <span x-text="copied ? @js(__('Copied!')) : @js(__('Copy link'))">{{ __('Copy link') }}</span>
    </button>
</div>
