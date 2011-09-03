<?php

namespace Cron;

use DateTime;
use DateInterval;

/**
 * Month field.  Allows: * , / -
 *
 * @author Michael Dowling <mtdowling@gmail.com>
 */
class MonthField extends AbstractField
{
    /**
     * {@inheritdoc}
     */
    public function isSatisfiedBy(DateTime $date, $value)
    {
        // Convert text month values to integers
        $value = strtr($value, array(
            'JAN' => 1,
            'FEB' => 2,
            'MAR' => 3,
            'APR' => 4,
            'MAY' => 5,
            'JUN' => 6,
            'JUL' => 7,
            'AUG' => 8,
            'SEP' => 9,
            'OCT' => 10,
            'NOV' => 11,
            'DEC' => 12
        ));

        return $this->isSatisfied($date->format('m'), $value);
    }

    /**
     * {@inheritdoc}
     */
    public function increment(DateTime $date, $invert = false)
    {
        $year = $date->format('Y');
        if ($invert) {
            $month = $date->format('m') - 1;
            if ($month < 1) {
                $month = 12;
                $year--;
            }
            $date->setDate($year, $month, 1);
            $date->setDate($year, $month, DayOfMonthField::getLastDayOfMonth($date));
            $date->setTime(23, 59, 0);
        } else {
            $month = $date->format('m') + 1;
            if ($month > 12) {
                $month = 1;
                $year++;
            }
            $date->setDate($year, $month, 1);
            $date->setTime(0, 0, 0);
        }

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