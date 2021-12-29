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
    public function isSatisfiedBy(string $fieldExpression, DateTimeInterface $date): bool;

    /**
     * When a CRON expression is not satisfied, this method is used to increment
     * a DateTime or a DateTimeImmutable object by the unit of the cron field.
     *
     * @param string|null $fieldExpression the field expression value if available
     */
    public function increment(DateTime|DateTimeImmutable $date, string|null $fieldExpression = null): DateTime|DateTimeImmutable;

    /**
     * When a CRON expression is not satisfied, this method is used to decrement
     * a DateTime or a DateTimeImmutable object by the unit of the cron field.
     *
     * @param string|null $fieldExpression the field expression value if available
     */
    public function decrement(DateTime|DateTimeImmutable $date, string|null $fieldExpression = null): DateTime|DateTimeImmutable;

    /**
     * Validates a CRON expression for a given field.
     */
    public function isValid(string $fieldExpression): bool;
}
