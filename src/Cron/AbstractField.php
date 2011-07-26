<?php

namespace Cron;

use InvalidArgumentException;

/**
 * Abstract CRON expression field
 *
 * @author Michael Dowling <mtdowling@gmail.com>
 */
abstract class AbstractField implements FieldInterface
{
    /**
     * Check to see if a field is satisfied by a value
     *
     * @param string $dateValue Date value to check
     * @param string $value Value to test
     *
     * @return bool
     */
    public function isSatisfied($dateValue, $value)
    {
        if ($this->isIncrementsOfRanges($value)) {
            return $this->isInIncrementsOfRanges($dateValue, $value);
        } else if ($this->isRange($value)) {
            return $this->isInRange($dateValue, $value);
        }

        return $value == '*' || $dateValue == $value;
    }

    /**
     * Check if a value is a range
     *
     * @param string $value Value to test
     *
     * @return bool
     */
    public function isRange($value)
    {
        return strpos($value, '-') !== false;
    }

    /**
     * Check if a value is an increments of ranges
     *
     * @param string $value Value to test
     *
     * @return bool
     */
    public function isIncrementsOfRanges($value)
    {
        return strpos($value, '/') !== false;
    }

    /**
     * Test if a value is within a range
     *
     * @param string $dateValue Set date value
     * @param string $value Value to test
     *
     * @return bool
     */
    public function isInRange($dateValue, $value)
    {
        $parts = array_map('trim', explode('-', $value, 2));

        return $dateValue >= $parts[0] && $dateValue <= $parts[1];
    }

    /**
     * Test if a value is within an increments of ranges
     *
     * @param string $dateValue Set date value
     * @param string $value Value to test
     *
     * @return bool
     */
    public function isInIncrementsOfRanges($dateValue, $value)
    {
        $parts = array_map('trim', explode('/', $value, 2));
        if ($parts[0] != '*' && $parts[0] != 0) {
            if (!strpos($parts[0], '-')) {
                throw new InvalidArgumentException('Invalid increments of ranges value: ' . $value);
            } else {
                list($after, $denominator) = explode('-', $parts[0]);
                if ($dateValue == $after) {
                    return true;
                } else if ($dateValue < $after) {
                    return false;
                }
            }
        }

        return (int) $dateValue % (int) $parts[1] == 0;
    }
}