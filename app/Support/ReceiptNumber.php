<?php

namespace App\Support;

use App\Models\ReceiptCounter;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReceiptNumber
{
    public static function next(Carbon $date): string
    {
        $day = $date->toDateString();

        DB::table('receipt_counters')->insertOrIgnore([
            'date' => $day,
            'last_sequence' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $counter = ReceiptCounter::where('date', $day)->lockForUpdate()->firstOrFail();
        $counter->increment('last_sequence');
        $counter->refresh();

        $prefix = Setting::get('receipt_prefix', 'RCPT');

        return sprintf('%s-%s-%06d', $prefix, $date->format('Ymd'), $counter->last_sequence);
    }
}
