@extends('layouts.admin')

@section('title', __('Audit log') . ' — ' . site_name())
@section('page-title', __('Audit log'))

@section('content')
<div class="space-y-5">

    <form method="GET" action="{{ route('admin.audit.index') }}" class="card card-pad flex flex-col sm:flex-row gap-3 sm:items-end">
        <div class="sm:max-w-xs w-full">
            <label for="action" class="label">{{ __('Action prefix') }}</label>
            <input type="text" id="action" name="action" value="{{ request('action') }}" class="input" placeholder="user., payment., settings.…">
        </div>
        <div class="flex gap-2">
            <button class="btn-primary btn-sm">{{ __('Filter') }}</button>
            @if(request('action'))
                <a href="{{ route('admin.audit.index') }}" class="btn-secondary btn-sm">{{ __('Reset') }}</a>
            @endif
        </div>
    </form>

    <div class="card overflow-x-auto">
        <table class="table-cards">
            <thead>
                <tr>
                    <th>{{ __('Time') }}</th>
                    <th>{{ __('User') }}</th>
                    <th>{{ __('Action') }}</th>
                    <th>{{ __('Target') }}</th>
                    <th>{{ __('Meta') }}</th>
                    <th>{{ __('IP') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td data-label="{{ __('Time') }}">
                            <span title="{{ $log->created_at->format('Y-m-d H:i:s') }}">{{ $log->created_at->diffForHumans() }}</span>
                        </td>
                        <td data-label="{{ __('User') }}">
                            @if($log->user)
                                <a href="{{ route('admin.users.edit', $log->user) }}" class="text-brand-600 hover:underline">{{ $log->user->name }}</a>
                            @else
                                <span class="text-slate-400">{{ __('System') }}</span>
                            @endif
                        </td>
                        <td data-label="{{ __('Action') }}"><span class="badge-gray font-mono text-xs">{{ $log->action }}</span></td>
                        <td data-label="{{ __('Target') }}">
                            @if($log->target_type)
                                <span class="text-xs">{{ class_basename($log->target_type) }} <span class="text-slate-400">#{{ $log->target_id }}</span></span>
                            @else
                                —
                            @endif
                        </td>
                        <td data-label="{{ __('Meta') }}">
                            @if($log->meta)
                                <code class="block max-w-[16rem] truncate text-xs text-slate-500" title="{{ json_encode($log->meta) }}">{{ \Illuminate\Support\Str::limit(json_encode($log->meta), 60) }}</code>
                            @else
                                —
                            @endif
                        </td>
                        <td data-label="{{ __('IP') }}" class="font-mono text-xs text-slate-500">{{ $log->ip ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-slate-500 py-10 sm:table-cell">{{ __('No log entries found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $logs->links() }}
</div>
@endsection
