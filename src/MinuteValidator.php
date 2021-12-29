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

    public function increment(DateTime|DateTimeImmutable $date, $invert = false, string $parts = null): DateTime|DateTimeImmutable
    {
        if (null === $parts) {
            return match (true) {
                true === $invert => $date->sub(new DateInterval('PT1M')),
                default => $date->add(new DateInterval('PT1M')),
            };
        }

        $minutes = array_reduce(
            str_contains($parts, ',') ? explode(',', $parts) : [$parts],
            fn (array $minutes, string $part): array => array_merge($minutes, $this->getRangeForExpression($part, 59)),
            []
        );

        $currentMinute = (int) $date->format('i');
        $minute = $minutes[$this->computePosition($currentMinute, $minutes, $invert)];

        if ((!$invert && $currentMinute >= $minute) || ($invert && $currentMinute <= $minute)) {
            return match (true) {
                true === $invert => $date->sub(new DateInterval('PT1H'))->setTime((int) $date->format('H'), 59),
                default => $date->add(new DateInterval('PT1H'))->setTime((int) $date->format('H'), 0),
            };
        }

        return $date->setTime((int) $date->format('H'), $minute);
    }
}
