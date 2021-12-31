<?php

namespace Bakame\Cron;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Generator;

interface CronScheduler
{
    /**
     * Returns the expression attached to the object.
     */
    public function expression(): CronExpression;

    /**
     * Returns the scheduler execution timezone.
     */
    public function timezone(): DateTimeZone;

    /**
     * Returns the scheduler maximun iteration count.
     */
    public function maxIterationCount(): int;

    /**
     * Tells whether to include or not the relative time when calculating the next run.
     */
    public function isStartDateExcluded(): bool;

    /**
     * Set the expression of the CRON scheduler.
     */
    public function withExpression(CronExpression $expression): self;

    /**
     * Set the timezone of the CRON scheduler.
     */
    public function withTimezone(DateTimeZone $timezone): self;

    /**
     * Set the max iteration count of the CRON scheduler.
     */
    public function withMaxIterationCount(int $maxIterationCount): self;

    /**
     * Include the relative time in the results if possible.
     */
    public function includeStartDate(): self;

    /**
     * Exclude the relative time in the results.
     */
    public function excludeStartDate(): self;

    /**
     * Get a run date relative to a specific date.
     *
     * @param DateTimeInterface|string $startDate Relative calculation date
     *                                            If the date is expressed with a string,
     *                                            the scheduler will assume the date uses the underlying system timezone
     *
     * @param int $nth Number of matches to skip before returning a matching next run date. 0, the default, will
     *                 return the current date and time if the next run date falls on the current date and time.
     *                 Setting this value to 1 will skip the first match and go to the second match.
     *                 Setting this value to 2 will skip the first 2 matches and so on.
     *                 If the number is negative the skipping will be done backward.
     *
     * @throws CronError on too many iterations
     */
    public function run(DateTimeInterface|string $startDate, int $nth = 0): DateTimeImmutable;

    /**
     * Determine if the cron is due to run based on a specific date.
     * This method assumes that the current number of seconds are irrelevant, and should be called once per minute.
     *
     * @param DateTimeInterface|string $when Specific date
     *                                       If the date is expressed with a string,
     *                                       the scheduler will assume the date uses the underlying system timezone
     */
    public function isDue(DateTimeInterface|string $when): bool;

    /**
     * Returns multiple run dates starting at least at the specific starting date and ending at most at the specified
     * end date. The last generated date will be after or equal to the specified date.
     *
     * @throws CronError
     * @return Generator<DateTimeImmutable>
     *
     *
     * @see Scheduler::yieldRunsBefore()
     * @see Scheduler::yieldRunsAfter()
     */
    public function yieldRuns(DateTimeInterface|string $startDate, DateTimeInterface|string $endDate): Generator;

    /**
     * Returns multiple run dates ending at most at the specific ending date and ending at most after the specified
     * interval.
     *
     * @throws CronError
     *
     * @return Generator<DateTimeImmutable>
     */
    public function yieldRunsBefore(DateTimeInterface|string $endDate, DateInterval|string $interval): Generator;

    /**
     * Returns multiple run dates starting at most at the specific starting date and ending at most after the specified
     * interval.
     *
     * @throws CronError
     *
     * @return Generator<DateTimeImmutable>
     */
    public function yieldRunsAfter(DateTimeInterface|string $endDate, DateInterval|string $interval): Generator;

    /**
     * Get multiple run dates starting at least at the current date or a specific date or after it.
     * The last generated date will be after or equal to the specified date.
     *
     * @param DateTimeInterface|string $startDate Relative calculation date
     * @param int $recurrences Set the total number of dates to calculate
     *                         If the date is expressed with a string,
     *                         the scheduler will assume the date uses the underlying system timezone
     *
     * @throws CronError
     *
     * @return Generator<DateTimeImmutable>
     */
    public function yieldRunsForward(DateTimeInterface|string $startDate, int $recurrences): Generator;

    /**
     * Get multiple run dates ending at most at the specified start date or before it.
     * The last generated date will be before or equal to the specified date.
     *
     * @param DateTimeInterface|string $endDate Relative calculation date
     * @param int $recurrences Set the total number of dates to calculate
     *                         If the date is expressed with a string,
     *                         the scheduler will assume the date uses the underlying system timezone
     *
     * @throws CronError
     * @return Generator<DateTimeImmutable>
     *
     *
     * @see Scheduler::yieldRunsForward
     */
    public function yieldRunsBackward(DateTimeInterface|string $endDate, int $recurrences): Generator;
}
