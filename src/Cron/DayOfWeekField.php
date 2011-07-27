<?php

namespace Cron;

use DateTime;
use DateInterval;

/**
 * Day of week field.  Allows: * / , - ? L #
 *
 * Days of the week can be represented as a number 0-7 (0|7 = Sunday)
 * or as a three letter string: SUN, MON, TUE, WED, THU, FRI, SAT.
 *
 * 'L' stands for "last". It allows you to specify constructs such as
 * "the last Friday" of a given month.
 *
 * @todo '#' is allowed for the day-of-week field, and must be followed by a
 * number between one and five. It allows you to specify constructs such as
 * "the second Friday" of a given month.
 *
 * @author Michael Dowling <mtdowling@gmail.com>
 */
class DayOfWeekField extends AbstractField
{
    /**
     * {@inheritdoc}
     */
    public function isSatisfiedBy(DateTime $date, $value)
    {
        if ($value == '?') {
            return true;
        }

        // Convert text day of the week values to integers
        $value = strtr($value, array(
            'SUN' => 0,
            'MON' => 1,
            'TUE' => 2,
            'WED' => 3,
            'THU' => 4,
            'FRI' => 5,
            'SAT' => 6
        ));

        // Find out if this is the last specific weekday of the month
        if (strpos($value, 'L')) {
            $weekday = str_replace('7', '0', substr($value, 0, strpos($value, 'L')));
            $lastDay = DayOfMonthField::getLastDayOfMonth($date);
            $currentYear = $date->format('Y');
            $currentMonth = $date->format('m');
            $tdate = clone $date;
            $tdate->setDate($currentYear, $currentMonth, $lastDay);
            while ($tdate->format('w') != $weekday) {
                $tdate->setDate($currentYear, $currentMonth, --$lastDay);
            }
            
            return $date->format('j') == $lastDay;
        }

        // Handle day of the week values
        if (strpos($value, '-')) {
            $parts = explode('-', $value);
            if ($parts[0] == '7') {
                $parts[0] = '0';
            } else if ($parts[1] == '0') {
                $parts[1] = '7';
            }
            $value = implode('-', $parts);
        }

        // Test to see which Sunday to use -- 0 == 7 == Sunday
        $format = in_array(7, str_split($value)) ? 'N' : 'w';
        $fieldValue = $date->format($format);

        return $this->isSatisfied($fieldValue, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function increment(DateTime $date)
    {
        $date->add(new DateInterval('P1D'));
        $date->setTime(0, 0, 0);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value)
    {
        return (bool) preg_match('/[\*,\/\-0-9A-Z]+/', $value);
    }
}