<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PasswordChangeController extends Controller
{
    public function edit()
    {
        return view('auth.change-password');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => __('auth.password'),
            ]);
        }

        $user->forceFill([
            'password' => $data['password'],
            'must_change_password' => false,
        ])->save();

        Audit::record('auth.password_changed', 'Password changed.', ['username' => $user->username]);

        return redirect()->route('dashboard')->with('status', __('messages.password_changed'));
    }
}
