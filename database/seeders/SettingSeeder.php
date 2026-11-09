<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'school_name' => config('app.name', 'schoolPoint'),
            'school_logo_path' => null,
            'currency' => 'USD',
            'student_id_prefix' => 'SP',
            'receipt_prefix' => 'RCPT',
            'timezone' => 'Africa/Mogadishu',
        ];

        foreach ($defaults as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forever('settings.timezone', $defaults['timezone']);
    }
}
