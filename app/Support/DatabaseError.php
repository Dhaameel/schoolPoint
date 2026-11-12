<?php

namespace App\Support;

use Illuminate\Database\QueryException;

class DatabaseError
{
    public static function isUniqueViolation(QueryException $exception): bool
    {
        return (string) $exception->getCode() === '23000'
            || (int) ($exception->errorInfo[1] ?? 0) === 1062
            || str_contains(strtolower($exception->getMessage()), 'unique constraint');
    }

    public static function isLockWaitTimeout(QueryException $exception): bool
    {
        return (int) ($exception->errorInfo[1] ?? 0) === 1205
            || str_contains(strtolower($exception->getMessage()), 'lock wait timeout');
    }
}
