<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

class SchoolDays
{
    public static function countBetween(Carbon $start, Carbon $end, array $schoolDays): int
    {
        if ($end->lt($start)) {
            return 0;
        }

        $count = 0;

        foreach (CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay()) as $day) {
            if (in_array($day->dayOfWeekIso, $schoolDays, true)) {
                $count++;
            }
        }

        return $count;
    }

    public static function isSchoolDay(Carbon $day, array $schoolDays): bool
    {
        return in_array($day->dayOfWeekIso, $schoolDays, true);
    }

    public static function datesBetween(Carbon $start, Carbon $end, array $schoolDays): array
    {
        if ($end->lt($start)) {
            return [];
        }

        $dates = [];

        foreach (CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay()) as $day) {
            if (in_array($day->dayOfWeekIso, $schoolDays, true)) {
                $dates[] = $day->toDateString();
            }
        }

        return $dates;
    }
}
