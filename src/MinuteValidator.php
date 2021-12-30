<?php

declare(strict_types=1);

namespace Bakame\Cron;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * Minutes field.  Allows: * , / -.
 */
final class MinuteValidator extends FieldValidator
{
    protected const RANGE_START = 0;
    protected const RANGE_END = 59;

    public function isSatisfiedBy(string $fieldExpression, DateTimeInterface $date): bool
    {
        return '?' === $fieldExpression
            || $this->isSatisfied((int) $date->format('i'), $fieldExpression);
    }

    public function increment(DateTimeInterface $date, string|null $fieldExpression = null): DateTimeImmutable
    {
        $date = $this->toDateTimeImmutable($date);

        if (null === $fieldExpression) {
            return $date->add(new DateInterval('PT1M'));
        }

        $minutes = array_reduce(
            str_contains($fieldExpression, ',') ? explode(',', $fieldExpression) : [$fieldExpression],
            fn (array $minutes, string $part): array => array_merge($minutes, $this->getRangeForExpression($part, 59)),
            []
        );

        $currentMinute = (int) $date->format('i');
        $minute = $minutes[$this->computePosition($currentMinute, $minutes, false)];

        if ($currentMinute >= $minute) {
            $date = $date->add(new DateInterval('PT1H'));

            return $date->setTime((int) $date->format('H'), 0);
        }

        return $date->setTime((int) $date->format('H'), $minute);
    }

    public function decrement(DateTimeInterface $date, string|null $fieldExpression = null): DateTimeImmutable
    {
        $date = $this->toDateTimeImmutable($date);

        if (null === $fieldExpression) {
            return $date->sub(new DateInterval('PT1M'));
        }

        $minutes = array_reduce(
            str_contains($fieldExpression, ',') ? explode(',', $fieldExpression) : [$fieldExpression],
            fn (array $minutes, string $part): array => array_merge($minutes, $this->getRangeForExpression($part, 59)),
            []
        );

        $currentMinute = (int) $date->format('i');
        $minute = $minutes[$this->computePosition($currentMinute, $minutes, true)];

        if ($currentMinute <= $minute) {
            $date = $date->sub(new DateInterval('PT1H'));

            return $date->setTime((int) $date->format('H'), 59);
        }

        return $date->setTime((int) $date->format('H'), $minute);
    }
}
