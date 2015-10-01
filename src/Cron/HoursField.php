<?php

namespace Cron;

/**
 * Hours field.  Allows: * , / -
 */
class HoursField extends AbstractField
{
    public function isSatisfiedBy(\DateTime $date, $value)
    {
        return $this->isSatisfied($date->format('H'), $value);
    }

    public function increment(\DateTime $date, $invert = false)
    {
        // Change timezone to UTC temporarily. This will
        // allow us to go back or forwards and hour even
        // if DST will be changed between the hours.
        $timezone = $date->getTimezone();
        $localMinutes = $date->format('i');
        $date->setTimezone(new \DateTimeZone('UTC'));
        // handle timezones with non-hour-offsets
        $utcMinutes = $date->format('i');
        $minDiff = $localMinutes - $utcMinutes;
        if ($invert) {
            $date->modify('-1 hour');
            $date->setTime($date->format('H'), 59 - $minDiff);
        } else {
            $date->modify('+1 hour');
            $date->setTime($date->format('H'), 0 - $minDiff);
        }
        $date->setTimezone($timezone);

        return $this;
    }

    public function validate($value)
    {
        return (bool) preg_match('/^[\*,\/\-0-9]+$/', $value);
    }
}
