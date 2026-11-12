<?php

namespace Tests\Unit;

use App\Support\ReportPeriod;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReportPeriodTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.timezone' => 'Africa/Mogadishu']);
        date_default_timezone_set('Africa/Mogadishu');
        Carbon::setTestNow(Carbon::parse('2026-11-12 10:00:00', 'Africa/Mogadishu'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function the_shared_date_range_presets_are_calculated_in_the_school_timezone(): void
    {
        $today = ReportPeriod::make('today');
        $this->assertSame('2026-11-12', $today->startDate());
        $this->assertSame('2026-11-12', $today->endDate());

        $week = ReportPeriod::make('this_week');
        $this->assertSame('2026-11-09', $week->startDate());
        $this->assertSame('2026-11-15', $week->endDate());

        $month = ReportPeriod::make('this_month');
        $this->assertSame('2026-11-01', $month->startDate());
        $this->assertSame('2026-11-30', $month->endDate());

        $year = ReportPeriod::make('this_year');
        $this->assertSame('2026-01-01', $year->startDate());
        $this->assertSame('2026-12-31', $year->endDate());
    }

    #[Test]
    public function a_custom_range_is_inclusive_and_preserved_in_report_links(): void
    {
        $period = ReportPeriod::make('custom', '2026-11-05', '2026-11-07');

        $this->assertSame('2026-11-05', $period->startDate());
        $this->assertSame('2026-11-07', $period->endDate());
        $this->assertSame([
            'range' => 'custom',
            'start_date' => '2026-11-05',
            'end_date' => '2026-11-07',
        ], $period->query());

        $this->assertSame(['range' => 'today'], ReportPeriod::make('today')->query());
        $this->assertSame('this_month', ReportPeriod::make('unknown')->range);
    }
}
