<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $key = 'login:'.Str::lower($credentials['username']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'username' => __('auth.throttle', ['seconds' => $seconds]),
            ]);
        }

        $user = User::query()->where('username', $credentials['username'])->first();

        if (! $user || ! $user->is_active || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'username' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($key);

        if ($user->hasEnabledTwoFactorAuthentication()) {
            Auth::logout();

            $request->session()->put('two_factor_user_id', $user->id);
            $request->session()->put('two_factor_remember', $request->boolean('remember'));

            return redirect()->route('two-factor.challenge');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();
        Audit::record('auth.login', 'User signed in.', ['username' => $user->username]);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        if ($request->user()) {
            Audit::record('auth.logout', 'User signed out.', ['username' => $request->user()->username]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
