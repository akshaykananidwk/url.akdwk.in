@extends('layouts.app')

@section('title', __('Activity') . ' — ' . site_name())
@section('page-title', __('Activity'))

@section('content')
<div class="max-w-3xl mx-auto space-y-5">

    @if($activities->isEmpty())
        <x-empty-state icon="clock" :title="__('No activity yet')" :description="__('Actions across your account and workspaces will show up here.')"/>
    @else
        <div class="card divide-y divide-slate-100 dark:divide-slate-800">
            @foreach($activities as $a)
                <div class="flex items-start gap-3 p-4 sm:px-5">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
                        <x-icon name="clock" class="h-4 w-4"/>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm">
                            <span class="font-medium">{{ $a->user?->name ?? __('Someone') }}</span>
                            <span class="text-slate-500 dark:text-slate-400">{{ \Illuminate\Support\Str::headline($a->action) }}</span>
                        </p>
                        @if($a->subject)
                            <p class="text-sm text-slate-600 dark:text-slate-300 truncate">{{ \Illuminate\Support\Str::limit($a->subject, 90) }}</p>
                        @endif
                        <p class="mt-0.5 text-xs text-slate-400">{{ $a->created_at?->diffForHumans() }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        @if($activities->hasPages())
            <div>{{ $activities->links() }}</div>
        @endif
    @endif
</div>
@endsection
