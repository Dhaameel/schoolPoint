@php
    $schoolName = \App\Models\Setting::get('school_name', config('app.name', 'schoolPoint'));
    $schoolLogo = \App\Models\Setting::get('school_logo_path');
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $schoolName)</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body class="auth-body">
    <div class="auth-shell">
        <aside class="auth-brand-panel">
            <a class="auth-brand" href="{{ url('/') }}">
                @if($schoolLogo)
                    <img src="{{ asset('storage/'.$schoolLogo) }}" alt="{{ $schoolName }}" style="width: 46px; height: 46px; object-fit: contain; border-radius: 12px; background: #fffdf5; padding: 4px;">
                @else
                    <span class="brand-mark"><i class="bi bi-mortarboard-fill"></i></span>
                @endif
                <span class="brand-copy">
                    <strong>{{ $schoolName }}</strong>
                    <small>{{ __('messages.tagline') }}</small>
                </span>
            </a>

            <div class="auth-brand-copy">
                <h1>{{ __('messages.auth_brand_headline') }}</h1>
                <p>{{ __('messages.auth_brand_copy') }}</p>

                <ul class="auth-feature-list">
                    <li><i class="bi bi-people"></i> {{ __('messages.students') }} &middot; {{ __('messages.teachers') }}</li>
                    <li><i class="bi bi-calendar2-check"></i> {{ __('messages.attendance') }}</li>
                    <li><i class="bi bi-cash-stack"></i> {{ __('messages.fees') }}</li>
                </ul>
            </div>

            <div class="auth-brand-footer">&copy; 2026 schoolPoint</div>
        </aside>

        <main class="auth-form-panel">
            <div class="auth-form-card">
                @yield('content')
            </div>
        </main>
    </div>

    @stack('scripts')
</body>
</html>
