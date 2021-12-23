<?php

namespace Bakame\Cron\Validator;

use DateTimeInterface;

/**
 * CRON field interface.
 */
interface Field
{
    /**
     * Check if the respective value of a DateTime field satisfies a CRON exp.
     *
     * @param DateTimeInterface $date DateTime object to check
     * @param string $expression CRON expression to test against
     *
     * @return bool Returns TRUE if satisfied, FALSE otherwise
     */
    public function isSatisfiedBy(DateTimeInterface $date, string $expression): bool;

    /**
     * When a CRON expression is not satisfied, this method is used to increment
     * or decrement a DateTime object by the unit of the cron field.
     *
     * @param DateTimeInterface &$date DateTime object to change
     * @param bool $invert (optional) Set to TRUE to decrement
     * @param string|null $parts (optional) Set to TRUE to decrement
     *
     */
    public function increment(DateTimeInterface $date, bool $invert = false, string $parts = null): DateTimeInterface;

    /**
     * Validates a CRON expression for a given field.
     *
     * @param string $expression CRON expression value to validate
     *
     * @return bool Returns TRUE if valid, FALSE otherwise
     */
    public function validate(string $expression): bool;
}
