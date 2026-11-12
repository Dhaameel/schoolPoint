<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function edit()
    {
        $settings = Setting::query()->pluck('value', 'key');

        return view('admin.settings.edit', [
            'settings' => $settings,
            'schoolDays' => \App\Models\Setting::schoolDays(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'school_name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'currency' => ['required', 'in:USD,KES,UGX,EUR,SOS,ETB,TZS'],
            'student_id_prefix' => ['required', 'alpha_num', 'max:5'],
            'receipt_prefix' => ['required', 'alpha_num', 'max:10'],
            'timezone' => ['required', 'timezone'],
            'school_days' => ['required', 'array', 'min:1'],
            'school_days.*' => ['integer', 'between:1,7'],
        ]);

        $data['school_days'] = json_encode(array_values(array_unique($data['school_days'])));

        $oldLogo = Setting::get('school_logo_path');

        if ($request->hasFile('logo')) {
            if ($oldLogo) {
                Storage::disk('public')->delete($oldLogo);
            }

            $data['school_logo_path'] = $request->file('logo')->store('settings', 'public');
        }

        unset($data['logo']);

        foreach ($data as $key => $value) {
            Setting::put($key, $value);
        }

        config(['app.timezone' => $data['timezone']]);
        date_default_timezone_set($data['timezone']);

        Audit::record('settings.updated', 'School settings updated.', [
            'keys' => array_keys($data),
        ]);

        return redirect()->route('settings.edit')->with('status', __('messages.settings_saved'));
    }
}
