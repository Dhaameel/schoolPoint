@php
    $adminItems = [
        ['route' => 'dashboard', 'label' => 'messages.dashboard', 'icon' => 'bi-grid', 'active' => request()->routeIs('dashboard')],
        ['route' => 'students.index', 'label' => 'messages.students', 'icon' => 'bi-people', 'active' => request()->routeIs('students.*')],
        ['route' => 'teachers.index', 'label' => 'messages.teachers', 'icon' => 'bi-person-video2', 'active' => request()->routeIs('teachers.*')],
        ['route' => 'classes.index', 'label' => 'messages.classes', 'icon' => 'bi-buildings', 'active' => request()->routeIs('classes.*')],
        ['route' => 'subjects.index', 'label' => 'messages.subjects', 'icon' => 'bi-journal-bookmark', 'active' => request()->routeIs('subjects.*')],
        ['route' => 'attendance.index', 'label' => 'messages.attendance', 'icon' => 'bi-calendar2-check', 'active' => request()->routeIs('attendance.*')],
        ['route' => 'fees.index', 'label' => 'messages.fees', 'icon' => 'bi-cash-stack', 'active' => request()->routeIs('fees.*')],
        ['route' => 'reports.index', 'label' => 'messages.reports', 'icon' => 'bi-graph-up', 'active' => request()->routeIs('reports.*')],
        ['route' => 'settings.edit', 'label' => 'messages.settings', 'icon' => 'bi-gear', 'active' => request()->routeIs('settings.*')],
    ];

    $teacherItems = [
        ['route' => 'dashboard', 'label' => 'messages.dashboard', 'icon' => 'bi-grid', 'active' => request()->routeIs('dashboard')],
        ['route' => 'teacher.classes', 'label' => 'messages.my_classes', 'icon' => 'bi-easel', 'active' => request()->routeIs('teacher.classes')],
        ['route' => 'teacher.attendance', 'label' => 'messages.attendance', 'icon' => 'bi-calendar2-check', 'active' => request()->routeIs('teacher.attendance')],
    ];

    $items = auth()->user()->isAdmin() ? $adminItems : $teacherItems;
@endphp

<div id="appSidebar" class="col-12 col-md-3 col-lg-2 app-sidebar collapse d-md-block">
    <div class="sidebar-inner">
        <ul class="nav flex-column">
            @foreach($items as $item)
                <li class="nav-item">
                    <a class="nav-link {{ $item['active'] ? 'active' : '' }}" href="{{ route($item['route']) }}">
                        <i class="bi {{ $item['icon'] }}"></i>
                        <span class="sidebar-label">{{ __($item['label']) }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</div>
