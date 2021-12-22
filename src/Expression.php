<?php

declare(strict_types=1);

namespace Cron;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Generator;

/**
 * CRON expression parser that can determine if a CRON expression is
 * due to run, the next run date and previous run date of a CRON expression.
 * The determinations made by this final class are accurate if checked run once per
 * minute (seconds are dropped from date time comparisons).
 *
 * Schedule parts must map to:
 * minute [0-59], hour [0-23], day of month, month [1-12|JAN-DEC], day of week
 * [1-7|MON-SUN], and an optional year.
 *
 * @link http://en.wikipedia.org/wiki/Cron
 */
interface Expression
{
    public const ALLOW_CURRENT_DATE = 1;
    public const DISALLOW_CURRENT_DATE = 0;

    /**
     * Get a next run date relative to the current date or a specific date.
     *
     * @param DateTimeInterface|string|null $from Relative calculation date
     * @param int $nth Number of occurrences to skip before returning a
     *                 matching next run date.  0, the default, will return the current
     *                 date and time if the next run date falls on the current date and
     *                 time.  Setting this value to 1 will skip the first match and go to
     *                 the second match.  Setting this value to 2 will skip the first 2
     *                 matches and so on.
     * @param int $options Set to self::ALLOW_CURRENT_DATE or self::DISALLOW_CURRENT_DATE to return or not
     *                     the current date if it matches the cron expression
     *
     * @throws ExpressionError
     */
    public function nextRun(DateTimeInterface|string|null $from = 'now', int $nth = 0, int $options = self::DISALLOW_CURRENT_DATE): DateTimeImmutable;

    /**
     * Get a previous run date relative to the current date or a specific date.
     *
     * @param DateTimeInterface|string|null $from Relative calculation date
     * @param int $nth Number of occurrences to skip before returning
     * @param int $options Set to self::ALLOW_CURRENT_DATE or self::DISALLOW_CURRENT_DATE to return or not
     *                     the current date if it matches the cron expression
     *
     * @throws ExpressionError
     *
     * @see self::getNextRunDate
     */
    public function previousRun(DateTimeInterface|string|null $from = 'now', int $nth = 0, int $options = self::DISALLOW_CURRENT_DATE): DateTimeImmutable;

    /**
     * Get multiple run dates starting at the current date or a specific date.
     *
     * @param int $total Set the total number of dates to calculate
     * @param DateTimeInterface|string|null $from Relative calculation date
     * @param int $options Set to self::ALLOW_CURRENT_DATE or self::DISALLOW_CURRENT_DATE to return or not
     *                     the current date if it matches the cron expression
     *
     * @throws ExpressionError
     *
     * @return Generator<DateTimeImmutable>
     */
    public function nextOccurrences(int $total, DateTimeInterface|string|null $from = 'now', int $options = self::DISALLOW_CURRENT_DATE): Generator;

    /**
     * Get multiple run dates starting at the current date or a specific date.
     *
     * @param int $total Set the total number of dates to calculate
     * @param DateTimeInterface|string|null $from Relative calculation date
     * @param int $options Set to self::ALLOW_CURRENT_DATE or self::DISALLOW_CURRENT_DATE to return or not
     *                     the current date if it matches the cron expression
     *
     * @throws ExpressionError
     *
     * @return Generator<DateTimeImmutable>
     *
     * @see CronExpression::nextOccurrences
     */
    public function previousOccurrences(int $total, DateTimeInterface|string|null $from = 'now', int $options = self::DISALLOW_CURRENT_DATE): Generator;

    /**
     * Determine if the cron is due to run based on the current date or a
     * specific date.  This method assumes that the current number of
     * seconds are irrelevant, and should be called once per minute.
     *
     * @param DateTimeInterface|string $datetime Relative calculation date
     *
     * @throws ExpressionError
     */
    public function match(DateTimeInterface|string $datetime = 'now'): bool;

    /**
     * @return array<int, string|int>
     */
    public function fields(): array;

    public function minute(): string;

    public function hour(): string;

    public function dayOfMonth(): string;

    public function month(): string;

    public function dayOfWeek(): string;

    public function toString(): string;

    public function timezone(): DateTimeZone;

    public function maxIterationCount(): int;

    public function withMinute(string $field): self;

    public function withHour(string $field): self;

    public function withDayOfMonth(string $field): self;

    public function withMonth(string $field): self;

    public function withDayOfWeek(string $field): self;

    public function withTimezone(DateTimeZone|string $timezone): self;

    public function withMaxIterationCount(int $maxIterationCount): self;
}
