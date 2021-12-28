<?php

declare(strict_types=1);

namespace Bakame\Cron;

/**
 * CRON expression object.
 */
interface CronExpression
{
    /**
     * Returns the CRON expression fields as array.
     *
     * @return array<int, string>
     */
    public function fields(): array;

    /**
     * Returns the minute field of the CRON expression.
     */
    public function minute(): string;

    /**
     * Returns the hour field of the CRON expression.
     */
    public function hour(): string;

    /**
     * Returns the day of the month field of the CRON expression.
     */
    public function dayOfMonth(): string;

    /**
     * Returns the month field of the CRON expression.
     */
    public function month(): string;

    /**
     * Returns the day of the week field of the CRON expression.
     */
    public function dayOfWeek(): string;

    /**
     * Returns the string representation of the CRON expression.
     */
    public function toString(): string;

    /**
     * Set the minute field of the CRON expression.
     *
     * @throws CronError if the value is not valid for the part
     *
     */
    public function withMinute(string $fieldExpression): self;

    /**
     * Set the hour field of the CRON expression.
     *
     * @throws CronError if the value is not valid for the part
     *
     */
    public function withHour(string $fieldExpression): self;

    /**
     * Set the day of month field of the CRON expression.
     *
     * @throws CronError if the value is not valid for the part
     *
     */
    public function withDayOfMonth(string $fieldExpression): self;

    /**
     * Set the month field of the CRON expression.
     *
     * @throws CronError if the value is not valid for the part
     *
     */
    public function withMonth(string $fieldExpression): self;

    /**
     * Set the day of the week field of the CRON expression.
     *
     * @throws CronError if the value is not valid for the part
     *
     */
    public function withDayOfWeek(string $fieldExpression): self;
}
