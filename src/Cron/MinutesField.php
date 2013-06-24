<?php

namespace Cron;

use DateTime;

/**
 * Minutes field.  Allows: * , / -
 *
 * @author Michael Dowling <mtdowling@gmail.com>
 */
class MinutesField extends AbstractField
{
    /**
     * {@inheritdoc}
     */
    public function isSatisfiedBy(DateTime $date, $value)
    {
        return $this->isSatisfied($date->format('i'), $value);
    }

    /**
     * {@inheritdoc}
     */
    public function increment(DateTime $date, $invert = false)
    {
        if ($invert) {
            $date->modify('-1 minute');
        } else {
            $date->modify('+1 minute');
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value)
    {
        return (bool) preg_match('/[\*,\/\-0-9]+/', $value);
    }
}
