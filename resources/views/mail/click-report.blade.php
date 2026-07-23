<x-mail::message>
# {{ __('Your :days-day link report', ['days' => $days]) }}

{{ __('Hi :name, here is how your links performed in the last :days days.', ['name' => $user->name, 'days' => $days]) }}

**{{ __('Total clicks') }}:** {{ number_format($totals['clicks']) }}
**{{ __('Unique visitors') }}:** {{ number_format($totals['uniques']) }}
**{{ __('QR scans') }}:** {{ number_format($totals['qr_scans']) }}

@if($topLinks->isNotEmpty())
## {{ __('Top links') }}

@foreach($topLinks as $link)
- **{{ $link->title ?: $link->alias }}** — {{ number_format($link->clicks_count) }} {{ __('clicks') }}
@endforeach
@endif

<x-mail::button :url="route('stats.global')">
{{ __('View full statistics') }}
</x-mail::button>

{{ __('You receive this because email reports are on in your notification settings.') }}

{{ __('Thanks') }},<br>
{{ site_name() }}
</x-mail::message>
