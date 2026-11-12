<?php

namespace App\Support;

use Carbon\Carbon;

final class ReportPeriod
{
    public const RANGES = ['today', 'this_week', 'this_month', 'this_year', 'custom'];

    private function __construct(
        public readonly string $range,
        public readonly Carbon $start,
        public readonly Carbon $end,
    ) {}

    public static function make(string $range, ?string $startDate = null, ?string $endDate = null): self
    {
        $now = now();

        return match ($range) {
            'today' => new self($range, $now->copy()->startOfDay(), $now->copy()->endOfDay()),
            'this_week' => new self(
                $range,
                $now->copy()->startOfWeek(Carbon::MONDAY)->startOfDay(),
                $now->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay()
            ),
            'this_year' => new self($range, $now->copy()->startOfYear()->startOfDay(), $now->copy()->endOfYear()->endOfDay()),
            'custom' => new self(
                $range,
                Carbon::parse($startDate ?? $now->toDateString())->startOfDay(),
                Carbon::parse($endDate ?? $now->toDateString())->endOfDay()
            ),
            default => new self('this_month', $now->copy()->startOfMonth()->startOfDay(), $now->copy()->endOfMonth()->endOfDay()),
        };
    }

    public function startDate(): string
    {
        return $this->start->toDateString();
    }

    public function endDate(): string
    {
        return $this->end->toDateString();
    }

    public function display(): string
    {
        return $this->start->format('j M Y').' – '.$this->end->format('j M Y');
    }

    public function query(): array
    {
        if ($this->range !== 'custom') {
            return ['range' => $this->range];
        }

        return [
            'range' => 'custom',
            'start_date' => $this->startDate(),
            'end_date' => $this->endDate(),
        ];
    }
}
