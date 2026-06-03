<?php

declare(strict_types=1);

namespace App\Logger;

/**
 * Determines whether the current time falls within working hours.
 */
class WorkingHoursChecker
{
    private int $startHour;
    private int $endHour;

    public function __construct(int $startHour = 7, int $endHour = 17)
    {
        $this->startHour = $startHour;
        $this->endHour = $endHour;
    }

    /**
     * Check if the given timestamp (or now) is within working hours (Mon-Fri, startHour-endHour).
     */
    public function isWorkingHours(?int $timestamp = null): bool
    {
        $timestamp = $timestamp ?? time();
        $hour = (int) date('H', $timestamp);
        $weekday = (int) date('N', $timestamp);

        return $weekday <= 5 && $hour >= $this->startHour && $hour < $this->endHour;
    }
}
