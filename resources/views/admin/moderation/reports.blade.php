@extends('layouts.admin')

@section('title', __('Abuse reports') . ' — ' . site_name())
@section('page-title', __('Abuse reports'))

@section('content')
<div class="space-y-5">

    <form method="GET" action="{{ route('admin.abuse.index') }}" class="card card-pad flex flex-col sm:flex-row gap-3 sm:items-end">
        <div class="sm:max-w-xs w-full">
            <label for="status" class="label">{{ __('Status') }}</label>
            <select id="status" name="status" class="input">
                <option value="">{{ __('All') }}</option>
                @foreach(['open' => __('Open'), 'resolved' => __('Resolved'), 'dismissed' => __('Dismissed')] as $s => $label)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn-primary btn-sm">{{ __('Filter') }}</button>
    </form>

    <div class="card overflow-x-auto">
        <table class="table-cards">
            <thead>
                <tr>
                    <th>{{ __('Reported link') }}</th>
                    <th>{{ __('Reason') }}</th>
                    <th>{{ __('Details') }}</th>
                    <th>{{ __('Reporter') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reports as $report)
                    <tr>
                        <td data-label="{{ __('Reported link') }}">
                            @if($report->link)
                                <a href="{{ $report->link->shortUrl() }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 font-medium hover:text-brand-600 min-w-0">
                                    <span class="truncate max-w-[12rem]">{{ $report->link->alias }}</span>
                                    <x-icon name="external" class="h-3.5 w-3.5 shrink-0 text-slate-400"/>
                                </a>
                            @else
                                <span class="block max-w-[14rem] truncate" title="{{ $report->url }}">{{ $report->url ?: '—' }}</span>
                            @endif
                        </td>
                        <td data-label="{{ __('Reason') }}"><span class="badge-amber">{{ __(ucfirst($report->reason)) }}</span></td>
                        <td data-label="{{ __('Details') }}">
                            @if($report->details)
                                <span x-data="{ expanded: false }" class="block max-w-[16rem]">
                                    <span x-show="!expanded" class="block truncate">{{ \Illuminate\Support\Str::limit($report->details, 60) }}</span>
                                    <span x-cloak x-show="expanded" class="block whitespace-pre-wrap text-sm">{{ $report->details }}</span>
                                    @if(strlen($report->details) > 60)
                                        <button type="button" class="text-xs text-brand-600 hover:underline min-h-touch" @click="expanded = !expanded"
                                                x-text="expanded ? @js(__('Show less')) : @js(__('Show more'))"></button>
                                    @endif
                                </span>
                            @else
                                —
                            @endif
                        </td>
                        <td data-label="{{ __('Reporter') }}" class="text-slate-500">{{ $report->email ?: __('Anonymous') }}</td>
                        <td data-label="{{ __('Status') }}">
                            @if($report->status === 'open')<span class="badge-amber">{{ __('Open') }}</span>
                            @elseif($report->status === 'resolved')<span class="badge-green">{{ __('Resolved') }}</span>
                            @else<span class="badge-gray">{{ __('Dismissed') }}</span>@endif
                        </td>
                        <td data-label="{{ __('Actions') }}" class="sm:text-end">
                            @if($report->status === 'open')
                                <span class="inline-flex items-center gap-1.5 flex-wrap">
                                    @if($report->link)
                                        <form method="POST" action="{{ route('admin.abuse.resolve', $report) }}">
                                            @csrf
                                            <input type="hidden" name="action" value="disable">
                                            <button class="btn-danger btn-sm">{{ __('Disable link') }}</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.abuse.resolve', $report) }}">
                                        @csrf
                                        <input type="hidden" name="action" value="resolve">
                                        <button class="btn-secondary btn-sm">{{ __('Resolve') }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.abuse.resolve', $report) }}">
                                        @csrf
                                        <input type="hidden" name="action" value="dismiss">
                                        <button class="btn-ghost btn-sm">{{ __('Dismiss') }}</button>
                                    </form>
                                </span>
                            @else
                                <span class="text-xs text-slate-400">{{ $report->updated_at->diffForHumans() }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-slate-500 py-10 sm:table-cell">{{ __('No reports — nice and quiet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $reports->links() }}
</div>
@endsection
