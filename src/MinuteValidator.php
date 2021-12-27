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

    public function isSatisfiedBy(DateTimeInterface $date, string $fieldExpression): bool
    {
        return $this->isSatisfied((int) $date->format('i'), $fieldExpression);
    }

    public function increment(DateTime|DateTimeImmutable $date, $invert = false, string $parts = null): DateTime|DateTimeImmutable
    {
        if (null === $parts) {
            if ($invert) {
                return $date->sub(new DateInterval('PT1M'));
            }

            return $date->add(new DateInterval('PT1M'));
        }

        $parts = str_contains($parts, ',') ? explode(',', $parts) : [$parts];
        $minutes = [];
        foreach ($parts as $part) {
            $minutes = array_merge($minutes, $this->getRangeForExpression($part, 59));
        }

        $currentMinute = (int) $date->format('i');
        $position = $this->computePosition($currentMinute, $minutes, $invert);

        if ((!$invert && $currentMinute >= $minutes[$position]) || ($invert && $currentMinute <= $minutes[$position])) {
            if ($invert) {
                return $date->sub(new DateInterval('PT1H'))->setTime((int) $date->format('H'), 59);
            }

            return $date->add(new DateInterval('PT1H'))->setTime((int) $date->format('H'), 0);
        }

        return $date->setTime((int) $date->format('H'), (int) $minutes[$position]);
    }
}
