<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        try {
            $timezone = Cache::rememberForever(
                'settings.timezone',
                fn () => Setting::get('timezone', config('app.timezone'))
            );
        } catch (\Throwable) {
            $timezone = Setting::get('timezone', config('app.timezone'));
        }

        if ($timezone && in_array($timezone, timezone_identifiers_list(), true)) {
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);
        }
    }
}
