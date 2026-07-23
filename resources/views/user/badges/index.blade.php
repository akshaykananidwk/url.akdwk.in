@extends('layouts.app')

@section('title', __('Badges') . ' — ' . site_name())
@section('page-title', __('Badges & streak'))

@section('content')
@php
    $stats = [
        'links' => (int) $user->links()->count(),
        'clicks' => (int) $user->links()->sum('clicks_count'),
        'streak' => (int) ($user->streak_days ?? 0),
        'member' => $user->created_at ? (int) $user->created_at->diffInDays(now()) : 0,
    ];
    $isPaid = ! $user->currentPlan()->is_free;
    $earnedCount = $earned->count();
    $totalCount = count($definitions);
@endphp
<div class="max-w-4xl mx-auto space-y-5">

    {{-- Streak + progress --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4">
        <x-stat-card :label="__('Current streak')" :value="format_number($stats['streak']) . ' ' . __('days')" icon="bolt"/>
        <x-stat-card :label="__('Badges earned')" :value="$earnedCount . ' / ' . $totalCount" icon="sparkles"/>
        <x-stat-card :label="__('Total clicks')" :value="format_number($stats['clicks'])" icon="chart" class="col-span-2 sm:col-span-1"/>
    </div>

    @if($stats['streak'] > 0)
        <div class="card card-pad !py-3 flex items-center gap-3 text-sm">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-950 dark:text-amber-400">
                <x-icon name="bolt" class="h-5 w-5"/>
            </span>
            <p class="text-slate-600 dark:text-slate-300">
                {{ __('You have been active :days days in a row. Come back tomorrow to keep it going!', ['days' => format_number($stats['streak'])]) }}
            </p>
        </div>
    @endif

    {{-- Badge grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
        @foreach($definitions as $key => $def)
            @php
                $badge = $earned->get($key);
                $isEarned = $badge !== null;
                $metric = $def['metric'] ?? 'links';
                $threshold = (int) ($def['threshold'] ?? 0);
                if ($metric === 'member' && $threshold === 0) {
                    $current = $isPaid ? 1 : 0;
                    $goal = 1;
                    $requirement = __('Upgrade to a paid plan');
                } else {
                    $current = $stats[$metric] ?? 0;
                    $goal = max(1, $threshold);
                    $requirement = match ($metric) {
                        'links' => __(':n links', ['n' => format_number($threshold)]),
                        'clicks' => __(':n total clicks', ['n' => format_number($threshold)]),
                        'streak' => __(':n day streak', ['n' => format_number($threshold)]),
                        'member' => __(':n days as a member', ['n' => format_number($threshold)]),
                        default => '',
                    };
                }
                $pct = $isEarned ? 100 : min(100, (int) floor(($current / $goal) * 100));
            @endphp
            <div class="card card-pad {{ $isEarned ? 'ring-2 !ring-amber-300 dark:!ring-amber-700' : 'opacity-80' }}">
                <div class="flex items-start gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl
                        {{ $isEarned
                            ? 'bg-amber-100 text-amber-600 dark:bg-amber-950 dark:text-amber-400'
                            : 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500' }}">
                        <x-icon :name="$def['icon'] ?? 'sparkles'" class="h-6 w-6"/>
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <h3 class="font-semibold truncate">{{ __($def['name']) }}</h3>
                            @if($isEarned)
                                <span class="badge-amber shrink-0"><x-icon name="check" class="h-3 w-3"/> {{ __('Earned') }}</span>
                            @endif
                        </div>
                        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ __($def['description']) }}</p>
                    </div>
                </div>

                @if($isEarned)
                    <p class="mt-3 text-xs text-slate-400">
                        {{ __('Unlocked :when', ['when' => optional($badge->awarded_at)->diffForHumans() ?? __('recently')]) }}
                    </p>
                @else
                    <div class="mt-3">
                        <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                            <span>{{ $requirement }}</span>
                            <span>{{ $pct }}%</span>
                        </div>
                        <div class="h-1.5 w-full rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden">
                            <div class="h-full rounded-full bg-brand-600" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

</div>
@endsection
