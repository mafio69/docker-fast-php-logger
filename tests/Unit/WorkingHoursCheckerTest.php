<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Logger\WorkingHoursChecker;
use PHPUnit\Framework\TestCase;

class WorkingHoursCheckerTest extends TestCase
{
    private WorkingHoursChecker $checker;

    protected function setUp(): void
    {
        $this->checker = new WorkingHoursChecker();
    }

    public function testWorkingHoursMondayMorning(): void
    {
        // Monday 2026-06-01 at 09:00
        $ts = mktime(9, 0, 0, 6, 1, 2026);
        $this->assertTrue($this->checker->isWorkingHours($ts));
    }

    public function testWorkingHoursFridayAfternoon(): void
    {
        // Friday 2026-06-05 at 15:30
        $ts = mktime(15, 30, 0, 6, 5, 2026);
        $this->assertTrue($this->checker->isWorkingHours($ts));
    }

    public function testWorkingHoursStartBoundary(): void
    {
        // Monday 2026-06-01 at 07:00 (start of work)
        $ts = mktime(7, 0, 0, 6, 1, 2026);
        $this->assertTrue($this->checker->isWorkingHours($ts));
    }

    public function testNotWorkingHoursEndBoundary(): void
    {
        // Monday 2026-06-01 at 17:00 (end of work — exclusive)
        $ts = mktime(17, 0, 0, 6, 1, 2026);
        $this->assertFalse($this->checker->isWorkingHours($ts));
    }

    public function testNotWorkingHoursBeforeStart(): void
    {
        // Monday 2026-06-01 at 06:59
        $ts = mktime(6, 59, 0, 6, 1, 2026);
        $this->assertFalse($this->checker->isWorkingHours($ts));
    }

    public function testNotWorkingHoursSaturday(): void
    {
        // Saturday 2026-06-06 at 10:00
        $ts = mktime(10, 0, 0, 6, 6, 2026);
        $this->assertFalse($this->checker->isWorkingHours($ts));
    }

    public function testNotWorkingHoursSunday(): void
    {
        // Sunday 2026-06-07 at 12:00
        $ts = mktime(12, 0, 0, 6, 7, 2026);
        $this->assertFalse($this->checker->isWorkingHours($ts));
    }

    public function testNotWorkingHoursLateNight(): void
    {
        // Wednesday 2026-06-03 at 23:00
        $ts = mktime(23, 0, 0, 6, 3, 2026);
        $this->assertFalse($this->checker->isWorkingHours($ts));
    }

    public function testNotWorkingHoursEarlyMorning(): void
    {
        // Tuesday 2026-06-02 at 05:00
        $ts = mktime(5, 0, 0, 6, 2, 2026);
        $this->assertFalse($this->checker->isWorkingHours($ts));
    }

    public function testCustomHoursRange(): void
    {
        $checker = new WorkingHoursChecker(9, 18);

        // Monday at 08:00 — before custom start
        $ts = mktime(8, 0, 0, 6, 1, 2026);
        $this->assertFalse($checker->isWorkingHours($ts));

        // Monday at 09:00 — at custom start
        $ts = mktime(9, 0, 0, 6, 1, 2026);
        $this->assertTrue($checker->isWorkingHours($ts));

        // Monday at 17:30 — within custom range
        $ts = mktime(17, 30, 0, 6, 1, 2026);
        $this->assertTrue($checker->isWorkingHours($ts));

        // Monday at 18:00 — at custom end (exclusive)
        $ts = mktime(18, 0, 0, 6, 1, 2026);
        $this->assertFalse($checker->isWorkingHours($ts));
    }

    public function testAllWeekdays(): void
    {
        // 2026-06-01 is Monday, 2026-06-05 is Friday
        for ($day = 1; $day <= 5; $day++) {
            $ts = mktime(12, 0, 0, 6, $day, 2026);
            $this->assertTrue(
                $this->checker->isWorkingHours($ts),
                "Day $day (weekday) at noon should be working hours"
            );
        }
    }
}
