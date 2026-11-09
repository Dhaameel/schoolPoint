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
<body class="app-body">
    <nav class="navbar sticky-top navbar-expand-md navbar-dark app-topbar">
        <div class="container-fluid px-3 px-lg-4">
            <button class="btn btn-link text-white d-md-none me-1 p-1" type="button"
                    data-bs-toggle="collapse" data-bs-target="#appSidebar"
                    aria-controls="appSidebar" aria-expanded="false" aria-label="Toggle sidebar">
                <i class="bi bi-list fs-3"></i>
            </button>

            <a class="app-brand" href="{{ route('dashboard') }}">
                @if($schoolLogo)
                    <img src="{{ asset('storage/'.$schoolLogo) }}" alt="{{ $schoolName }}" style="width: 40px; height: 40px; object-fit: contain; border-radius: 10px; background: #fffdf5; padding: 3px;">
                @else
                    <span class="brand-mark"><i class="bi bi-mortarboard-fill"></i></span>
                @endif
                <span class="brand-copy">
                    <strong>{{ $schoolName }}</strong>
                    <small>{{ __('messages.tagline') }}</small>
                </span>
            </a>

            <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse"
                    data-bs-target="#topbarUser" aria-controls="topbarUser"
                    aria-expanded="false" aria-label="Toggle user menu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-end" id="topbarUser">
                <ul class="navbar-nav align-items-md-center gap-1">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#"
                           role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="user-avatar"><i class="bi bi-person-fill"></i></span>
                            <span class="text-white">{{ auth()->user()->name }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li class="dropdown-header">
                                {{ auth()->user()->isAdmin() ? __('messages.administrator') : __('messages.teacher') }}
                                &middot; {{ auth()->user()->username }}
                            </li>
                            @if(auth()->user()->isAdmin())
                                <li><a class="dropdown-item" href="{{ route('security.index') }}"><i class="bi bi-shield-lock me-2"></i>{{ __('messages.security') }}</a></li>
                            @endif
                            <li><a class="dropdown-item" href="{{ route('password.change') }}"><i class="bi bi-key me-2"></i>{{ __('messages.change_password') }}</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="dropdown-item" type="submit"><i class="bi bi-door-open me-2"></i>{{ __('messages.sign_out') }}</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid app-shell">
        <div class="row g-0">
            @include('layouts.sidebar')
            <main class="col app-main">
                @if(session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif
                @yield('content')
                <footer class="app-footer">&copy; 2026 schoolPoint</footer>
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
