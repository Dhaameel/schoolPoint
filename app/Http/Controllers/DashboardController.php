<?php

namespace App\Http\Controllers;

use App\Models\Setting;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('dashboard', [
            'user' => auth()->user(),
            'schoolName' => Setting::get('school_name', config('app.name')),
        ]);
    }
}
