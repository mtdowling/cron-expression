<?php

namespace Bakame\Cron;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * CRON field validator interface.
 */
interface CronFieldValidator
{
    /**
     * Check if the respective value of a DateTime field satisfies a CRON exp.
     *
     * @param DateTimeInterface $date DateTime object to check
     * @param string $fieldExpression CRON expression to test against
     *
     * @return bool Returns TRUE if satisfied, FALSE otherwise
     */
    public function isSatisfiedBy(DateTimeInterface $date, string $fieldExpression): bool;

    /**
     * When a CRON expression is not satisfied, this method is used to increment
     * or decrement a DateTime object by the unit of the cron field.
     *
     * @param DateTime|DateTimeImmutable $date DateTime object to change
     * @param bool $invert (optional) Set to TRUE to decrement
     * @param string|null $parts (optional) Set parts to use
     */
    public function increment(DateTime|DateTimeImmutable $date, bool $invert = false, string|null $parts = null): DateTime|DateTimeImmutable;

    /**
     * Validates a CRON expression for a given field.
     *
     * @param string $fieldExpression CRON expression value to validate
     *
     * @return bool Returns TRUE if valid, FALSE otherwise
     */
    public function validate(string $fieldExpression): bool;
}
