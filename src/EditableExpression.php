<?php

namespace Bakame\Cron;

use DateTimeZone;

interface EditableExpression extends Expression
{
    /**
     * Returns the max iteration count for searching next run dates.
     */
    public function maxIterationCount(): int;

    /**
     * Returns the timezone attached to the CRON expression.
     */
    public function timezone(): DateTimeZone;

    /**
     * Set the minute field of the CRON expression.
     *
     * @param string $field The value to set
     *
     * @throws ExpressionError if the value is not valid for the part
     *
     */
    public function withMinute(string $field): self;

    /**
     * Set the hour field of the CRON expression.
     *
     * @param string $field The value to set
     *
     * @throws ExpressionError if the value is not valid for the part
     *
     */
    public function withHour(string $field): self;

    /**
     * Set the day of month field of the CRON expression.
     *
     * @param string $field The value to set
     *
     * @throws ExpressionError if the value is not valid for the part
     *
     */
    public function withDayOfMonth(string $field): self;

    /**
     * Set the month field of the CRON expression.
     *
     * @param string $field The value to set
     *
     * @throws ExpressionError if the value is not valid for the part
     *
     */
    public function withMonth(string $field): self;

    /**
     * Set the day of the week field of the CRON expression.
     *
     * @param string $field The value to set
     *
     * @throws ExpressionError if the value is not valid for the part
     *
     */
    public function withDayOfWeek(string $field): self;

    /**
     * Set max iteration count when searching for next run dates.
     */
    public function withMaxIterationCount(int $maxIterationCount): self;

    /**
     * Sets the timezone attached to the CRON expression.
     */
    public function withTimezone(DateTimeZone|string $timezone): self;
}
