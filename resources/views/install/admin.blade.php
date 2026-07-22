@extends('install.layout')

@php($step = 4)

@section('title', __('Admin account'))

@section('content')
<h1>{{ __('Admin account & site settings') }}</h1>
<p class="muted">{{ __('Create the administrator account and set the basics — you can change all of this later in the admin panel.') }}</p>

@if($errors->any())
    <div class="alert alert-red">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('install.admin.store') }}">
    @csrf

    <label for="name">{{ __('Your name') }}</label>
    <input type="text" id="name" name="name" value="{{ old('name') }}" required maxlength="100" autofocus autocomplete="name">

    <label for="email">{{ __('Admin email') }}</label>
    <input type="email" id="email" name="email" value="{{ old('email') }}" required maxlength="190" autocomplete="email">

    <div class="grid-2">
        <div>
            <label for="password">{{ __('Password') }}</label>
            <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
        </div>
        <div>
            <label for="password_confirmation">{{ __('Confirm password') }}</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8" autocomplete="new-password">
        </div>
    </div>

    <label for="site_name">{{ __('Site name') }}</label>
    <input type="text" id="site_name" name="site_name" value="{{ old('site_name', 'Shortl') }}" required maxlength="100">

    <label for="site_url">{{ __('Site URL') }}</label>
    <input type="url" id="site_url" name="site_url" value="{{ old('site_url', url('/')) }}" required>

    <label for="default_language">{{ __('Default language') }}</label>
    <select id="default_language" name="default_language" required>
        <option value="en" @selected(old('default_language', 'en') === 'en')>English</option>
        <option value="gu" @selected(old('default_language') === 'gu')>ગુજરાતી</option>
        <option value="hi" @selected(old('default_language') === 'hi')>हिन्दी</option>
    </select>

    <div class="grid-2">
        <div>
            <label for="timezone">{{ __('Timezone') }}</label>
            <select id="timezone" name="timezone" required>
                @foreach($timezones as $tz)
                    <option value="{{ $tz }}" @selected(old('timezone', 'UTC') === $tz)>{{ $tz }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="currency">{{ __('Currency') }}</label>
            <select id="currency" name="currency" required>
                @foreach($currencies as $currency)
                    <option value="{{ $currency }}" @selected(old('currency', 'USD') === $currency)>{{ $currency }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="actions">
        <button type="submit" class="btn btn-primary">{{ __('Create admin & continue') }} &rarr;</button>
    </div>
</form>
@endsection
