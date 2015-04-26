<?php

namespace Cron;

/**
 * Minutes field.  Allows: * , / -
 */
class MinutesField extends AbstractField
{
    public function isSatisfiedBy(\DateTime $date, $value)
    {
        return $this->isSatisfied($date->format('i'), $value);
    }

    public function increment(\DateTime $date, $invert = false)
    {
        if ($invert) {
            $date->modify('-1 minute');
            if($date->format('i')=='59'){
                $date->modify('+1 hour');
            }
        } else {
            $date->modify('+1 minute');
            if($date->format('i')=='0'){
                $date->modify('-1 hour');
            }
        }

        return $this;
    }

    public function validate($value)
    {
        return (bool) preg_match('/^[\*,\/\-0-9]+$/', $value);
    }
}
