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
        $value = str_ireplace(
            array(
                'JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN',
                'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'
            ),
            range(1, 12),
            $value
        );

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
            $date->setDate($year, $month, $date->format('t'));
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