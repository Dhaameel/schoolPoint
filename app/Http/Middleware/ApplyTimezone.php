<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ApplyTimezone
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $timezone = Cache::rememberForever('settings.timezone', fn () => Setting::get('timezone', config('app.timezone')));
        } catch (\Throwable) {
            $timezone = Setting::get('timezone', config('app.timezone'));
        }

        if ($timezone && in_array($timezone, timezone_identifiers_list(), true)) {
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);
        }

        return $next($request);
    }
}
