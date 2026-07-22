@extends('install.layout')

@php($step = 3)

@section('title', __('Database'))

@section('content')
<h1>{{ __('Database connection') }}</h1>
<p class="muted">{{ __('Tell the installer where to store your data. MySQL is recommended for production; SQLite works great for small setups.') }}</p>

@if($errors->any())
    <div class="alert alert-red">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('install.database.store') }}" id="db-form">
    @csrf

    <label for="driver">{{ __('Database driver') }}</label>
    <select id="driver" name="driver">
        <option value="mysql" @selected(old('driver', 'mysql') === 'mysql')>MySQL / MariaDB</option>
        <option value="sqlite" @selected(old('driver') === 'sqlite')>{{ __('SQLite (simple, single-file)') }}</option>
    </select>

    <div id="mysql-fields">
        <div class="grid-2">
            <div>
                <label for="host">{{ __('Host') }}</label>
                <input type="text" id="host" name="host" value="{{ old('host', '127.0.0.1') }}" autocomplete="off">
            </div>
            <div>
                <label for="port">{{ __('Port') }}</label>
                <input type="number" id="port" name="port" value="{{ old('port', 3306) }}" min="1" max="65535">
            </div>
        </div>
        <label for="database">{{ __('Database name') }}</label>
        <input type="text" id="database" name="database" value="{{ old('database') }}" autocomplete="off">
        <p class="note">{{ __("If the database doesn't exist, the installer will try to create it.") }}</p>
        <div class="grid-2">
            <div>
                <label for="username">{{ __('Username') }}</label>
                <input type="text" id="username" name="username" value="{{ old('username') }}" autocomplete="off">
            </div>
            <div>
                <label for="password">{{ __('Password') }}</label>
                <input type="password" id="password" name="password" value="{{ old('password') }}" autocomplete="new-password">
            </div>
        </div>
    </div>

    <label for="prefix">{{ __('Table prefix (optional)') }}</label>
    <input type="text" id="prefix" name="prefix" value="{{ old('prefix') }}" maxlength="10" pattern="[a-zA-Z0-9_]*" autocomplete="off" placeholder="shortl_">

    <label class="check">
        <input type="checkbox" name="demo_data" value="1" @checked(old('demo_data'))>
        {{ __('Install demo data') }}
    </label>

    <div id="test-result"></div>

    <div class="actions">
        <button type="button" class="btn btn-secondary" id="test-btn">{{ __('Test Connection') }}</button>
        <button type="submit" class="btn btn-primary" id="install-btn">{{ __('Install') }} &rarr;</button>
    </div>
</form>

<script>
(function () {
    var driver = document.getElementById('driver');
    var mysqlFields = document.getElementById('mysql-fields');
    var form = document.getElementById('db-form');
    var testBtn = document.getElementById('test-btn');
    var installBtn = document.getElementById('install-btn');
    var result = document.getElementById('test-result');
    var csrf = document.querySelector('meta[name="csrf-token"]').content;

    function toggleFields() {
        mysqlFields.style.display = driver.value === 'mysql' ? '' : 'none';
    }
    driver.addEventListener('change', toggleFields);
    toggleFields();

    function payload() {
        return {
            driver: driver.value,
            host: document.getElementById('host').value,
            port: document.getElementById('port').value,
            database: document.getElementById('database').value,
            username: document.getElementById('username').value,
            password: document.getElementById('password').value,
            prefix: document.getElementById('prefix').value,
        };
    }

    testBtn.addEventListener('click', function () {
        testBtn.disabled = true;
        testBtn.textContent = @json(__('Testing…'));
        result.innerHTML = '';
        fetch(@json(route('install.database.test')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify(payload()),
        })
            .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
            .then(function (r) {
                var cls = (r.ok && r.data.ok) ? 'alert-green' : 'alert-red';
                var div = document.createElement('div');
                div.className = 'alert ' + cls;
                div.textContent = r.data.message || (r.ok ? 'OK' : 'Error');
                result.appendChild(div);
            })
            .catch(function () {
                var div = document.createElement('div');
                div.className = 'alert alert-red';
                div.textContent = @json(__('The connection test request failed. Please try again.'));
                result.appendChild(div);
            })
            .finally(function () {
                testBtn.disabled = false;
                testBtn.textContent = @json(__('Test Connection'));
            });
    });

    form.addEventListener('submit', function () {
        installBtn.disabled = true;
        testBtn.disabled = true;
        installBtn.textContent = @json(__('Running migrations…'));
    });
})();
</script>
@endsection
