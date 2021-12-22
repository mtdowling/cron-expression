<?php

declare(strict_types=1);

namespace Cron;

use DateTime;
use DateTimeInterface;
use DateTimeZone;

/**
 * Hours field.  Allows: * , / -.
 */
final class HoursField extends AbstractField
{
    protected int $rangeStart = 0;
    protected int $rangeEnd = 23;

    public function isSatisfiedBy(DateTimeInterface $date, string $expression): bool
    {
        return $this->isSatisfied($date->format('H'), $expression);
    }

    public function increment(DateTime $date, bool $invert = false, string $parts = null): void
    {
        // Change timezone to UTC temporarily. This will
        // allow us to go back or forwards and hour even
        // if DST will be changed between the hours.
        if (is_null($parts) || $parts == '*') {
            $timezone = $date->getTimezone();
            $date->setTimezone(new DateTimeZone('UTC'));
            if ($invert) {
                $date->modify('-1 hour');
            } else {
                $date->modify('+1 hour');
            }
            $date->setTimezone($timezone);
            $date->setTime((int) $date->format('H'), $invert ? 59 : 0);

            return;
        }

        $parts = str_contains($parts, ',') ? explode(',', $parts) : [$parts];
        $hours = [];
        foreach ($parts as $part) {
            $hours = array_merge($hours, $this->getRangeForExpression($part, 23));
        }

        $currentHour = (int) $date->format('H');
        $position = $this->computePosition($currentHour, $hours, $invert);

        $hour = $hours[$position];
        if ((!$invert && $date->format('H') >= $hour) || ($invert && $date->format('H') <= $hour)) {
            $date->modify(($invert ? '-' : '+').'1 day');
            $date->setTime($invert ? 23 : 0, $invert ? 59 : 0);
        } else {
            $date->setTime((int) $hour, $invert ? 59 : 0);
        }
    }
}
