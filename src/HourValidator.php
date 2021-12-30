<?php

declare(strict_types=1);

namespace Bakame\Cron;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

/**
 * Hours field.  Allows: * , / -.
 */
final class HourValidator extends FieldValidator
{
    protected int $rangeStart = 0;
    protected int $rangeEnd = 23;

    public function isSatisfiedBy(string $fieldExpression, DateTimeInterface $date): bool
    {
        return $this->isSatisfied((int) $date->format('H'), $fieldExpression);
    }

    public function increment(DateTime|DateTimeImmutable $date, string|null $fieldExpression = null): DateTime|DateTimeImmutable
    {
        // Change timezone to UTC temporarily. This will
        // allow us to go back or forwards and hour even
        // if DST will be changed between the hours.
        if (null === $fieldExpression || $fieldExpression == '*') {
            $interval = new DateInterval('PT1H');
            $timezone = $date->getTimezone();
            $date = $date->setTimezone(new DateTimeZone('UTC'));
            $date = $date->add($interval)->setTimezone($timezone);

            return $date->setTime((int) $date->format('H'), 0);
        }

        $hours = array_reduce(
            str_contains($fieldExpression, ',') ? explode(',', $fieldExpression) : [$fieldExpression],
            fn (array $hours, string $part): array => array_merge($hours, $this->getRangeForExpression($part, 23)),
            []
        );

        $hour = $hours[$this->computePosition((int) $date->format('H'), $hours, false)];
        if ($date->format('H') >= $hour) {
            return $date->add(new DateInterval('P1D'))->setTime(0, 0);
        }

        return $date->setTime($hour, 0);
    }

    public function decrement(DateTime|DateTimeImmutable $date, string|null $fieldExpression = null): DateTime|DateTimeImmutable
    {
        // Change timezone to UTC temporarily. This will
        // allow us to go back or forwards and hour even
        // if DST will be changed between the hours.
        if (null === $fieldExpression || $fieldExpression == '*') {
            $interval = new DateInterval('PT1H');
            $timezone = $date->getTimezone();
            $date = $date->setTimezone(new DateTimeZone('UTC'));
            $date = $date->sub($interval)->setTimezone($timezone);

            return $date->setTime((int) $date->format('H'), 59);
        }

        $hours = array_reduce(
            str_contains($fieldExpression, ',') ? explode(',', $fieldExpression) : [$fieldExpression],
            fn (array $hours, string $part): array => array_merge($hours, $this->getRangeForExpression($part, 23)),
            []
        );

        $hour = $hours[$this->computePosition((int) $date->format('H'), $hours, true)];
        if ($date->format('H') <= $hour) {
            return $date->sub(new DateInterval('P1D'))->setTime(23, 59);
        }

        return $date->setTime($hour, 59);
    }
}
