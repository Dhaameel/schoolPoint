@php
    $schoolName = \App\Models\Setting::get('school_name', config('app.name', 'schoolPoint'));
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $schoolName)</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body class="app-body">
    <main class="container py-4">
        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
