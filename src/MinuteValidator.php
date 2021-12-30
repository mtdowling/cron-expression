<?php

declare(strict_types=1);

namespace Bakame\Cron;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * Minutes field.  Allows: * , / -.
 */
final class MinuteValidator extends FieldValidator
{
    protected int $rangeStart = 0;
    protected int $rangeEnd = 59;

    public function isSatisfiedBy(string $fieldExpression, DateTimeInterface $date): bool
    {
        return '?' === $fieldExpression
            || $this->isSatisfied((int) $date->format('i'), $fieldExpression);
    }

    public function increment(DateTime|DateTimeImmutable $date, string|null $fieldExpression = null): DateTime|DateTimeImmutable
    {
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

    public function decrement(DateTime|DateTimeImmutable $date, string|null $fieldExpression = null): DateTime|DateTimeImmutable
    {
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
