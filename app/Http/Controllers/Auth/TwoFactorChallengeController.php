<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorChallengeController extends Controller
{
    public function show(Request $request)
    {
        if (! $request->session()->has('two_factor_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function verify(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $userId = $request->session()->get('two_factor_user_id');
        $user = $userId ? User::query()->find($userId) : null;

        if (! $user || ! $user->is_active || ! $user->hasEnabledTwoFactorAuthentication()) {
            $request->session()->forget(['two_factor_user_id', 'two_factor_remember']);

            return redirect()->route('login')->withErrors(['username' => __('auth.failed')]);
        }

        $code = trim($request->string('code')->toString());
        $google2fa = new Google2FA();
        $valid = false;

        if ($google2fa->verifyKey($user->two_factor_secret, $code)) {
            $valid = true;
        } else {
            $codes = $user->two_factor_recovery_codes ?? [];
            $index = array_search($code, $codes, true);

            if ($index !== false) {
                unset($codes[$index]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();
                $valid = true;
            }
        }

        if (! $valid) {
            return back()->withErrors(['code' => __('auth.two_factor_invalid')]);
        }

        $remember = (bool) $request->session()->pull('two_factor_remember', false);
        $request->session()->forget('two_factor_user_id');

        Auth::login($user, $remember);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();
        Audit::record('auth.login', 'User signed in with two-factor authentication.', ['username' => $user->username]);

        return redirect()->intended(route('dashboard'));
    }
}
