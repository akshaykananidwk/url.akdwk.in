@extends('install.layout')

@php($step = 5)

@section('title', __('Installation complete'))

@section('content')
<div style="text-align:center; padding: 10px 0 4px;">
    <div style="width:64px;height:64px;border-radius:50%;background:#dcfce7;color:#16a34a;font-size:30px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">&#10003;</div>
    <h1>{{ __('Installation complete!') }}</h1>
    <p class="muted">{{ __('Your site is ready. Log in with the admin account you just created.') }}</p>
    <div class="actions" style="justify-content:center;">
        <a href="{{ $loginUrl }}" class="btn btn-primary">{{ __('Go to admin login') }} &rarr;</a>
    </div>
</div>

<h1 style="font-size:17px; margin-top:26px;">{{ __('Recommended next steps') }}</h1>
<ul class="list">
    <li style="display:block;">
        <strong>{{ __('1. Set up the cron job') }}</strong>
        <p class="note">{{ __('Required for scheduled tasks (link expiry, subscription renewals, stats rollups):') }}</p>
        <pre>* * * * * cd {{ base_path() }} &amp;&amp; php artisan schedule:run &gt;&gt; /dev/null 2&gt;&amp;1</pre>
    </li>
    <li style="display:block;">
        <strong>{{ __('2. Run a queue worker') }}</strong>
        <p class="note">{{ __('Click tracking and emails are queued. Keep a worker running (e.g. via Supervisor):') }}</p>
        <pre>php artisan queue:work</pre>
    </li>
    <li style="display:block;">
        <strong>{{ __('3. Read the documentation') }}</strong>
        <p class="note">{{ __('See the docs/ folder shipped with your download for gateways, custom domains, SMTP and more.') }}</p>
    </li>
</ul>
@endsection
