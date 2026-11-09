@extends('layouts.app')

@section('title', __('messages.dashboard'))

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ __('messages.dashboard') }}</h1>
            <p>{{ __('messages.welcome_back') }}, {{ $user->name }}.</p>
        </div>
        <span class="badge bg-primary">{{ $user->isAdmin() ? __('messages.administrator') : __('messages.teacher') }}</span>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="dashboard-card h-100">
                <div class="dashboard-card-head"><h2>{{ __('messages.foundation_ready') }}</h2></div>
                <p class="text-muted mb-3">{{ __('messages.foundation_help') }}</p>
                <div class="d-flex flex-wrap gap-2">
                    @if($user->isAdmin())
                        <a class="btn btn-primary" href="{{ route('settings.edit') }}"><i class="bi bi-gear me-1"></i>{{ __('messages.settings') }}</a>
                        <a class="btn btn-outline-primary" href="{{ route('students.index') }}"><i class="bi bi-people me-1"></i>{{ __('messages.students') }}</a>
                    @else
                        <a class="btn btn-primary" href="{{ route('teacher.classes') }}"><i class="bi bi-easel me-1"></i>{{ __('messages.my_classes') }}</a>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="dashboard-card h-100">
                <div class="dashboard-card-head"><h2>{{ $schoolName }}</h2></div>
                <dl class="row mb-0">
                    <dt class="col-5">{{ __('messages.username') }}</dt>
                    <dd class="col-7 text-end">{{ $user->username }}</dd>
                    <dt class="col-5">{{ __('messages.profile') }}</dt>
                    <dd class="col-7 text-end">{{ $user->isAdmin() ? __('messages.administrator') : __('messages.teacher') }}</dd>
                </dl>
            </div>
        </div>

        <div class="col-12">
            <div class="dashboard-card">
                <div class="dashboard-card-head"><h2>{{ __('messages.quick_links') }}</h2></div>
                <div class="row g-2">
                    @if($user->isAdmin())
                        @foreach([
                            ['route' => 'students.index', 'label' => 'messages.students', 'icon' => 'bi-people'],
                            ['route' => 'teachers.index', 'label' => 'messages.teachers', 'icon' => 'bi-person-video2'],
                            ['route' => 'classes.index', 'label' => 'messages.classes', 'icon' => 'bi-buildings'],
                            ['route' => 'subjects.index', 'label' => 'messages.subjects', 'icon' => 'bi-journal-bookmark'],
                            ['route' => 'attendance.index', 'label' => 'messages.attendance', 'icon' => 'bi-calendar2-check'],
                            ['route' => 'fees.index', 'label' => 'messages.fees', 'icon' => 'bi-cash-stack'],
                            ['route' => 'reports.index', 'label' => 'messages.reports', 'icon' => 'bi-graph-up'],
                            ['route' => 'settings.edit', 'label' => 'messages.settings', 'icon' => 'bi-gear'],
                        ] as $link)
                            <div class="col-sm-6 col-xl-3">
                                <a class="btn btn-outline-primary w-100 text-start" href="{{ route($link['route']) }}">
                                    <i class="bi {{ $link['icon'] }} me-1"></i>{{ __($link['label']) }}
                                </a>
                            </div>
                        @endforeach
                    @else
                        <div class="col-sm-6">
                            <a class="btn btn-outline-primary w-100 text-start" href="{{ route('teacher.classes') }}">
                                <i class="bi bi-easel me-1"></i>{{ __('messages.my_classes') }}
                            </a>
                        </div>
                        <div class="col-sm-6">
                            <a class="btn btn-outline-primary w-100 text-start" href="{{ route('teacher.attendance') }}">
                                <i class="bi bi-calendar2-check me-1"></i>{{ __('messages.attendance') }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
