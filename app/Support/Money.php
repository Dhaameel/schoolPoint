<?php

namespace App\Support;

class Money
{
    public static function normalize(mixed $value): string
    {
        $value = ltrim(trim((string) $value), '+');

        if (! preg_match('/^(?:\d+(?:\.\d{1,2})?|\.\d{1,2})$/', $value)) {
            return '0.00';
        }

        [$whole, $decimal] = array_pad(explode('.', $value, 2), 2, '');
        $whole = ltrim($whole, '0');

        return ($whole === '' ? '0' : $whole).'.'.str_pad($decimal, 2, '0');
    }

    public static function format(mixed $amount, ?string $currency = null): string
    {
        $formatted = number_format((float) $amount, 2);

        return $currency ? $formatted.' '.$currency : $formatted;
    }
}
