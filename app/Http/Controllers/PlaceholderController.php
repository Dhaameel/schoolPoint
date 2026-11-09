<?php

namespace App\Http\Controllers;

class PlaceholderController extends Controller
{
    public function admin(string $section)
    {
        return $this->page($section, [
            'students' => ['title' => 'Students', 'icon' => 'bi-people', 'stage' => 2],
            'teachers' => ['title' => 'Teachers', 'icon' => 'bi-person-video2', 'stage' => 2],
            'classes' => ['title' => 'Classes', 'icon' => 'bi-buildings', 'stage' => 2],
            'subjects' => ['title' => 'Subjects', 'icon' => 'bi-journal-bookmark', 'stage' => 2],
            'attendance' => ['title' => 'Attendance', 'icon' => 'bi-calendar2-check', 'stage' => 3],
            'fees' => ['title' => 'Fees', 'icon' => 'bi-cash-stack', 'stage' => 4],
            'reports' => ['title' => 'Reports', 'icon' => 'bi-graph-up', 'stage' => 5],
        ]);
    }

    public function teacher(string $section)
    {
        return $this->page($section, [
            'my-classes' => ['title' => 'My classes', 'icon' => 'bi-easel', 'stage' => 3],
            'attendance' => ['title' => 'Attendance', 'icon' => 'bi-calendar2-check', 'stage' => 3],
        ]);
    }

    private function page(string $section, array $pages)
    {
        abort_unless(isset($pages[$section]), 404);

        return view('placeholders.page', [
            'title' => $pages[$section]['title'],
            'icon' => $pages[$section]['icon'],
            'stage' => $pages[$section]['stage'],
        ]);
    }
}
