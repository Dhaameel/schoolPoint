<?php

namespace App\Support;

class Currencies
{
    public const ALL = ['USD', 'KES', 'UGX', 'EUR', 'SOS', 'ETB', 'TZS'];

    public static function all(): array
    {
        return self::ALL;
    }
}
