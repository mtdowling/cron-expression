<?php

declare(strict_types=1);

namespace Bakame\Cron\Validator;

use DateInterval;
use DateTimeInterface;
use DateTimeZone;

/**
 * Hours field.  Allows: * , / -.
 */
final class Hours extends Field
{
    protected int $rangeStart = 0;
    protected int $rangeEnd = 23;

    public function isSatisfiedBy(DateTimeInterface $date, string $expression): bool
    {
        return $this->isSatisfied((int) $date->format('H'), $expression);
    }

    public function increment(DateTimeInterface $date, bool $invert = false, string $parts = null): DateTimeInterface
    {
        // Change timezone to UTC temporarily. This will
        // allow us to go back or forwards and hour even
        // if DST will be changed between the hours.
        if (is_null($parts) || $parts == '*') {
            $timezone = $date->getTimezone();
            $date = $date->setTimezone(new DateTimeZone('UTC'));
            $interval = new DateInterval('PT1H');
            if ($invert) {
                return $date->sub($interval)->setTimezone($timezone)->setTime((int) $date->format('H'), 59);
            }

            return $date->add($interval)->setTimezone($timezone)->setTime((int) $date->format('H'), 0);
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
            if ($invert) {
                return $date->sub(new DateInterval('P1D'))->setTime(23, 59);
            }

            return $date->add(new DateInterval('P1D'))->setTime(0, 0);
        }

        if ($invert) {
            return $date->setTime((int) $hour, 59);
        }

        return $date->setTime((int) $hour, 0);
    }
}
