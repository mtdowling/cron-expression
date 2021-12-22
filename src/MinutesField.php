<?php

declare(strict_types=1);

namespace Cron;

use DateTime;
use DateTimeInterface;

/**
 * Minutes field.  Allows: * , / -.
 */
final class MinutesField extends AbstractField
{
    protected int $rangeStart = 0;
    protected int $rangeEnd = 59;

    public function isSatisfiedBy(DateTimeInterface $date, string $expression): bool
    {
        return $this->isSatisfied($date->format('i'), $expression);
    }

    public function increment(DateTime $date, $invert = false, string $parts = null): void
    {
        if (is_null($parts)) {
            if ($invert) {
                $date->modify('-1 minute');
            } else {
                $date->modify('+1 minute');
            }
            return;
        }

        $parts = str_contains($parts, ',') ? explode(',', $parts) : [$parts];
        $minutes = [];
        foreach ($parts as $part) {
            $minutes = array_merge($minutes, $this->getRangeForExpression($part, 59));
        }

        $currentMinute = (int) $date->format('i');
        $position = $this->computePosition($currentMinute, $minutes, $invert);

        if ((!$invert && $currentMinute >= $minutes[$position]) || ($invert && $currentMinute <= $minutes[$position])) {
            $date->modify(($invert ? '-' : '+').'1 hour');
            $date->setTime((int) $date->format('H'), $invert ? 59 : 0);
        } else {
            $date->setTime((int) $date->format('H'), (int) $minutes[$position]);
        }
    }
}
