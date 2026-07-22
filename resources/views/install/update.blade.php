@extends('install.layout')

@php($step = 5)

@section('title', __('Update'))

@section('content')
<h1>{{ __('Application update') }}</h1>

<ul class="list">
    <li>
        <span>{{ __('Installed version') }}</span>
        <strong>v{{ $currentVersion }}</strong>
    </li>
    <li>
        <span>{{ __('New version (uploaded files)') }}</span>
        <strong>v{{ $newVersion }}</strong>
    </li>
</ul>

@if(count($pending) === 0)
    <div class="alert alert-green">{{ __('Your database is up to date — no pending migrations.') }}</div>
    @if($currentVersion !== $newVersion)
        <p class="muted">{{ __('Running the update will still clear caches and record the new version number.') }}</p>
    @endif
@else
    <h1 style="font-size:17px;">{{ __('Pending migrations') }} ({{ count($pending) }})</h1>
    <ul class="list">
        @foreach($pending as $migration)
            <li><span style="font-family:monospace; font-size:12.5px;">{{ $migration }}</span></li>
        @endforeach
    </ul>
@endif

<form method="POST" action="{{ route('update.run') }}"
      onsubmit="if (!confirm(@js(__('Run the update now? Make sure you have a database backup.')))) return false; this.querySelector('button').disabled = true; this.querySelector('button').textContent = @js(__('Updating…'));">
    @csrf
    <div class="actions">
        <button type="submit" class="btn btn-primary">{{ __('Run update') }}</button>
        <a href="{{ url('/') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
    </div>
</form>
@endsection
