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

        $current_minute = $date->format('i');
        $position = $invert ? count($minutes) - 1 : 0;
        if (count($minutes) > 1) {
            for ($i = 0; $i < count($minutes) - 1; $i++) {
                if ((!$invert && $current_minute >= $minutes[$i] && $current_minute < $minutes[$i + 1]) ||
                    ($invert && $current_minute > $minutes[$i] && $current_minute <= $minutes[$i + 1])) {
                    $position = $invert ? $i : $i + 1;
                    break;
                }
            }
        }

        if ((!$invert && $current_minute >= $minutes[$position]) || ($invert && $current_minute <= $minutes[$position])) {
            $date->modify(($invert ? '-' : '+').'1 hour');
            $date->setTime((int) $date->format('H'), $invert ? 59 : 0);
        } else {
            $date->setTime((int) $date->format('H'), (int) $minutes[$position]);
        }
    }
}
