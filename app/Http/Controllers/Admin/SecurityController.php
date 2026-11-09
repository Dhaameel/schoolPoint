<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Audit;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class SecurityController extends Controller
{
    public function index()
    {
        return view('admin.security.index', [
            'user' => auth()->user(),
            'recoveryCodes' => session('recovery_codes', []),
        ]);
    }

    public function setup(Request $request)
    {
        $secret = $request->session()->get('two_factor_setup_secret')
            ?? (new Google2FA())->generateSecretKey();

        $request->session()->put('two_factor_setup_secret', $secret);

        $company = Setting::get('school_name', config('app.name'));
        $url = (new Google2FA())->getQRCodeUrl($company, $request->user()->username, $secret);

        $renderer = new ImageRenderer(new RendererStyle(220, 1), new SvgImageBackEnd());
        $svg = (new Writer($renderer))->writeString($url);

        return view('admin.security.setup', [
            'secret' => $secret,
            'qrCode' => 'data:image/svg+xml;base64,'.base64_encode($svg),
        ]);
    }

    public function enable(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $secret = $request->session()->get('two_factor_setup_secret');

        if (! $secret) {
            return redirect()->route('security.two-factor.setup');
        }

        if (! (new Google2FA())->verifyKey($secret, trim($request->string('code')->toString()))) {
            return back()->withErrors(['code' => __('auth.two_factor_invalid')]);
        }

        $codes = collect(range(1, 8))
            ->map(fn () => Str::upper(Str::random(4).'-'.Str::random(4)))
            ->all();

        $request->user()->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $codes,
            'two_factor_confirmed_at' => now(),
        ])->save();

        $request->session()->forget('two_factor_setup_secret');

        Audit::record('admins.two_factor_enabled', 'Two-factor authentication enabled.');

        return redirect()->route('security.index')
            ->with('status', __('messages.two_factor_enabled'))
            ->with('recovery_codes', $codes);
    }

    public function disable(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($request->string('password')->toString(), $request->user()->password)) {
            return back()->withErrors(['password' => __('auth.password')]);
        }

        $request->user()->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        Audit::record('admins.two_factor_disabled', 'Two-factor authentication disabled.');

        return redirect()->route('security.index')->with('status', __('messages.two_factor_disabled'));
    }
}
