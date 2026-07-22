@php($errors = $errors ?? new \Illuminate\Support\ViewErrorBag)
@extends('install.layout')

@php($step = 2)

@section('title', __('License'))

@section('content')
<h1>{{ __('License verification') }}</h1>
<p class="muted">{{ __('Enter the purchase code you received with your license to continue.') }}</p>

@if($errors->any())
    <div class="alert alert-red">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('install.license.store') }}">
    @csrf
    <label for="purchase_code">{{ __('Purchase code') }}</label>
    <input type="text" id="purchase_code" name="purchase_code" value="{{ old('purchase_code') }}"
           required minlength="8" autocomplete="off" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" autofocus>
    <p class="note">{{ __('You can find this code in your purchase confirmation email or your marketplace downloads page.') }}</p>

    <div class="actions">
        <button type="submit" class="btn btn-primary">{{ __('Verify & continue') }} &rarr;</button>
        <a href="{{ route('install.requirements') }}" class="btn btn-secondary">&larr; {{ __('Back') }}</a>
    </div>
</form>
@endsection
