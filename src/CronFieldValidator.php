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
     * Check if the respective value of a DateTimeInterface object satisfies a CRON exp. field.
     */
    public function isSatisfiedBy(DateTimeInterface $date, string $fieldExpression): bool;

    /**
     * When a CRON expression is not satisfied, this method is used to increment
     * or decrement a DateTime or a DateTimeImmutable object by the unit of the cron field.
     *
     * @param bool $invert Set to TRUE to decrement
     * @param string|null $parts Set parts to use
     */
    public function increment(DateTime|DateTimeImmutable $date, bool $invert = false, string|null $parts = null): DateTime|DateTimeImmutable;

    /**
     * Validates a CRON expression for a given field.
     */
    public function isValid(string $fieldExpression): bool;
}
