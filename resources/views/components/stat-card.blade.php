@props(['label', 'value', 'icon' => 'chart', 'trend' => null])
<div {{ $attributes->merge(['class' => 'card card-pad !p-4 sm:!p-5']) }}>
    <div class="flex items-center justify-between gap-2">
        <span class="text-xs sm:text-sm font-medium text-slate-500 dark:text-slate-400">{{ $label }}</span>
        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
            <x-icon :name="$icon" class="h-4 w-4"/>
        </span>
    </div>
    <div class="mt-1.5 text-xl sm:text-2xl font-bold tracking-tight">{{ $value }}</div>
    @if($trend !== null)
        <div class="mt-1 text-xs {{ $trend >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
            {{ $trend >= 0 ? '▲' : '▼' }} {{ abs($trend) }}% {{ __('vs previous period') }}
        </div>
    @endif
</div>
