<?php

declare(strict_types=1);

namespace Bakame\Cron;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

/**
 * Hours field.  Allows: * , / -.
 */
final class HourValidator extends FieldValidator
{
    protected const RANGE_START = 0;
    protected const RANGE_END = 23;

    protected function isSatisfiedExpression(string $fieldExpression, DateTimeInterface $date): bool
    {
        return $this->isSatisfied((int) $date->format('H'), $fieldExpression);
    }

    public function increment(DateTimeInterface $date, string|null $fieldExpression = null): DateTimeImmutable
    {
        $date = $this->toDateTimeImmutable($date);

        // Change timezone to UTC temporarily. This will
        // allow us to go back or forwards and hour even
        // if DST will be changed between the hours.
        if (in_array($fieldExpression, [null, '*'], true)) {
            $timezone = $date->getTimezone();
            $date = $date
                ->setTimezone(new DateTimeZone('UTC'))
                ->add(new DateInterval('PT1H'))
                ->setTimezone($timezone);

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

    public function decrement(DateTimeInterface $date, string|null $fieldExpression = null): DateTimeImmutable
    {
        $date = $this->toDateTimeImmutable($date);

        // Change timezone to UTC temporarily. This will
        // allow us to go back or forwards and hour even
        // if DST will be changed between the hours.
        if (in_array($fieldExpression, [null, '*'], true)) {
            $timezone = $date->getTimezone();
            $date = $date
                ->setTimezone(new DateTimeZone('UTC'))
                ->sub(new DateInterval('PT1H'))
                ->setTimezone($timezone);

            return $date->setTime((int) $date->format('H'), 59);
        }

        /** @var array<int> $hours */
        $hours = array_reduce(
            str_contains($fieldExpression, ',') ? explode(',', $fieldExpression) : [$fieldExpression],
            fn (array $hours, string $part): array => array_merge($hours, $this->getRangeForExpression($part, 23)),
            []
        );

        $hour = $hours[$this->computePosition((int) $date->format('H'), $hours, true)];
        if ((int) $date->format('H') <= $hour) {
            return $date->sub(new DateInterval('P1D'))->setTime(23, 59);
        }

        return $date->setTime($hour, 59);
    }
}
