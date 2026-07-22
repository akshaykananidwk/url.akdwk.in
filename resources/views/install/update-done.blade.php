@extends('install.layout')

@php($step = 5)

@section('title', __('Update complete'))

@section('content')
<div style="text-align:center; padding: 10px 0 4px;">
    <div style="width:64px;height:64px;border-radius:50%;background:#dcfce7;color:#16a34a;font-size:30px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">&#10003;</div>
    <h1>{{ __('Update complete!') }}</h1>
    <p class="muted">{{ __('Migrations ran successfully and caches were cleared.') }}</p>
</div>

<h1 style="font-size:17px; margin-top:22px;">{{ __('Migration output') }}</h1>
<pre>{{ trim($output) !== '' ? trim($output) : __('Nothing to migrate.') }}</pre>

<div class="actions">
    <a href="{{ url('/admin') }}" class="btn btn-primary">{{ __('Go to admin panel') }} &rarr;</a>
    <a href="{{ url('/') }}" class="btn btn-secondary">{{ __('View site') }}</a>
</div>
@endsection
