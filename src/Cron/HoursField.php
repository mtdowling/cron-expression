<?php

declare(strict_types=1);

namespace Cron;

use DateTime;
use DateTimeZone;

/**
 * Hours field.  Allows: * , / -.
 */
class HoursField extends AbstractField
{
    /**
     * {@inheritdoc}
     */
    protected $rangeStart = 0;

    /**
     * {@inheritdoc}
     */
    protected $rangeEnd = 23;

    /**
     * {@inheritdoc}
     */
    public function isSatisfiedBy(DateTime $date, string $value): bool
    {
        return $this->isSatisfied((int) $date->format('H'), $value);
    }

    /**
     * {@inheritdoc}
     *
     * @param null|string $parts
     */
    public function increment(DateTime $date, bool $invert = false, ?string $parts = null): FieldInterface
    {
        // Change timezone to UTC temporarily. This will
        // allow us to go back or forwards and hour even
        // if DST will be changed between the hours.
        if (null === $parts || '*' === $parts) {
            $timezone = $date->getTimezone();
            $date->setTimezone(new DateTimeZone('UTC'));
            if ($invert) {
                $date->modify('-1 hour');
            } else {
                $date->modify('+1 hour');
            }
            $date->setTimezone($timezone);

            $date->setTime((int) $date->format('H'), $invert ? 59 : 0);

            return $this;
        }

        $parts = false !== strpos($parts, ',') ? explode(',', $parts) : [$parts];
        $hours = [];
        foreach ($parts as $part) {
            $hours = array_merge($hours, $this->getRangeForExpression($part, 23));
        }

        $current_hour = $date->format('H');
        $position = $invert ? \count($hours) - 1 : 0;
        $countHours = \count($hours);
        if ($countHours > 1) {
            for ($i = 0; $i < $countHours - 1; ++$i) {
                if ((!$invert && $current_hour >= $hours[$i] && $current_hour < $hours[$i + 1]) ||
                    ($invert && $current_hour > $hours[$i] && $current_hour <= $hours[$i + 1])) {
                    $position = $invert ? $i : $i + 1;

                    break;
                }
            }
        }

        $hour = (int) $hours[$position];
        if ((!$invert && (int) $date->format('H') >= $hour) || ($invert && (int) $date->format('H') <= $hour)) {
            $date->modify(($invert ? '-' : '+') . '1 day');
            $date->setTime($invert ? 23 : 0, $invert ? 59 : 0);
        } else {
            $date->setTime($hour, $invert ? 59 : 0);
        }

        return $this;
    }
}
