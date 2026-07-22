@extends('install.layout')

@php($step = 1)

@section('title', __('Server requirements'))

@section('content')
<h1>{{ __('Server requirements') }}</h1>
<p class="muted">{{ __('The installer checks your server before continuing. Fix anything marked red and re-check.') }}</p>

<ul class="list">
    <li>
        <span>PHP &ge; 8.2 <span class="note">({{ __('detected') }}: {{ $checks['php']['version'] }})</span></span>
        @if($checks['php']['ok'])
            <span class="ok"><span class="dot-ok"></span>&#10003;</span>
        @else
            <span class="fail"><span class="dot-fail"></span>&#10007;</span>
        @endif
    </li>
    @foreach($checks['extensions'] as $ext => $ok)
        <li>
            <span>{{ __('PHP extension') }}: <strong>{{ $ext }}</strong></span>
            @if($ok)
                <span class="ok"><span class="dot-ok"></span>&#10003;</span>
            @else
                <span class="fail"><span class="dot-fail"></span>&#10007;</span>
            @endif
        </li>
    @endforeach
</ul>

<h1 style="font-size:17px">{{ __('Writable paths') }}</h1>
<ul class="list">
    @foreach($checks['writable'] as $path => $ok)
        <li>
            <span><strong>{{ $path }}</strong></span>
            @if($ok)
                <span class="ok"><span class="dot-ok"></span>{{ __('writable') }}</span>
            @else
                <span class="fail"><span class="dot-fail"></span>{{ __('not writable') }}</span>
            @endif
        </li>
    @endforeach
</ul>

@unless($pass)
    <div class="alert alert-red">{{ __('Some requirements are not met. Install the missing PHP extensions / fix permissions, then re-check.') }}</div>
@endunless

<div class="actions">
    <a href="{{ route('install.license') }}" class="btn btn-primary {{ $pass ? '' : 'disabled' }}" @unless($pass) aria-disabled="true" tabindex="-1" @endunless>
        {{ __('Continue') }} &rarr;
    </a>
    <a href="{{ route('install.requirements') }}" class="btn btn-secondary">{{ __('Re-check') }}</a>
</div>
@endsection
