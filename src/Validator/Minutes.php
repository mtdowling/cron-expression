<?php

declare(strict_types=1);

namespace Bakame\Cron\Validator;

use DateInterval;
use DateTime;
use DateTimeInterface;

/**
 * Minutes field.  Allows: * , / -.
 */
final class Minutes extends Field
{
    protected int $rangeStart = 0;
    protected int $rangeEnd = 59;

    public function isSatisfiedBy(DateTimeInterface $date, string $expression): bool
    {
        return $this->isSatisfied((int) $date->format('i'), $expression);
    }

    public function increment(DateTime $date, $invert = false, string $parts = null): DateTime
    {
        if (null === $parts) {
            $interval = new DateInterval('PT1M');
            if ($invert) {
                return $date->sub($interval);
            }

            return $date->add($interval);
        }

        $parts = str_contains($parts, ',') ? explode(',', $parts) : [$parts];
        $minutes = [];
        foreach ($parts as $part) {
            $minutes = array_merge($minutes, $this->getRangeForExpression($part, 59));
        }

        $currentMinute = (int) $date->format('i');
        $position = $this->computePosition($currentMinute, $minutes, $invert);

        if ((!$invert && $currentMinute >= $minutes[$position]) || ($invert && $currentMinute <= $minutes[$position])) {
            $interval = new DateInterval('PT1H');
            if ($invert) {
                return $date->sub($interval)->setTime((int) $date->format('H'), 59);
            }

            return $date->add($interval)->setTime((int) $date->format('H'), 0);
        }

        return $date->setTime((int) $date->format('H'), (int) $minutes[$position]);
    }
}
