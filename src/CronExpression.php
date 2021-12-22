<?php

declare(strict_types=1);

namespace Cron;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use Generator;
use InvalidArgumentException;
use RuntimeException;
use Stringable;

/**
 * CRON expression parser that can determine whether or not a CRON expression is
 * due to run, the next run date and previous run date of a CRON expression.
 * The determinations made by this final classare accurate if checked run once per
 * minute (seconds are dropped from date time comparisons).
 *
 * Schedule parts must map to:
 * minute [0-59], hour [0-23], day of month, month [1-12|JAN-DEC], day of week
 * [1-7|MON-SUN], and an optional year.
 *
 * @link http://en.wikipedia.org/wiki/Cron
 */
final class CronExpression implements Stringable
{
    public const ALLOW_CURRENT_DATE = 1;
    public const DISALLOW_CURRENT_DATE = 0;
    private const MINUTE = 0;
    private const HOUR = 1;
    private const MONTHDAY = 2;
    private const MONTH = 3;
    private const WEEKDAY = 4;
    /**
     * Order in which to test of cron parts.
     */
    private const TEST_ORDER_CRON_PARTS = [
        self::MONTH,
        self::MONTHDAY,
        self::WEEKDAY,
        self::HOUR,
        self::MINUTE,
    ];

    /**
     * @var array<int, int|string> CRON expression parts
     */
    private array $cronParts;
    private int $maxIterationCount = 1000;

    /**
     * Get an instance of a field object for a cron expression position.
     *
     * @param int $position CRON expression position value to retrieve
     *
     * @throws InvalidArgumentException if a position is not valid
     */
    private static function field(int $position): FieldInterface
    {
        static $fields = [];
        if (!isset($fields[$position])) {
            $fields[$position] = match ($position) {
                self::MINUTE => new MinutesField(),
                self::HOUR => new HoursField(),
                self::MONTHDAY => new DayOfMonthField(),
                self::MONTH => new MonthField(),
                self::WEEKDAY => new DayOfWeekField(),
                default => throw new InvalidArgumentException($position.' is not a valid position'),
            };
        }

        return $fields[$position];
    }

    /**
     * Parse a CRON expression.
     *
     * @param string       $expression CRON expression (e.g. '8 * * * *')
     * @param DateTimeZone $timezone   CRON timezone
     */
    private function __construct(string $expression, private DateTimeZone $timezone)
    {
        /** @var array $cronParts */
        $cronParts = preg_split('/\s/', $expression, -1, PREG_SPLIT_NO_EMPTY);
        if (count($cronParts) < 5) {
            throw new InvalidArgumentException($expression.' is not a valid CRON expression');
        }

        foreach ($cronParts as $position => $part) {
            $this->setPart($position, $part);
        }
    }

    /**
     * Set part of the CRON expression.
     *
     * @param int    $position The position of the CRON expression to set
     * @param string $value    The value to set
     *
     * @throws InvalidArgumentException if the value is not valid for the part
     */
    private function setPart(int $position, string $value): void
    {
        if (!self::field($position)->validate($value)) {
            throw new InvalidArgumentException('Invalid CRON field value '.$value.' at position '.$position);
        }

        $this->cronParts[$position] = $value;
    }

    /**
     * Factory method to create a new CronExpression.
     *
     * @param string $expression The CRON expression to create.  There are
     *                           several special predefined values which can be used to substitute the
     *                           CRON expression:
     *
     *      `@yearly`, `@annually` - Run once a year, midnight, Jan. 1 - 0 0 1 1 *
     *      `@monthly` - Run once a month, midnight, first of month - 0 0 1 * *
     *      `@weekly` - Run once a week, midnight on Sun - 0 0 * * 0
     *      `@daily`, `@midnight` - Run once a day, midnight - 0 0 * * *
     *      `@hourly` - Run once an hour, first minute - 0 * * * *
     */
    public static function fromString(string $expression, DateTimeZone|string|null $timezone = null): self
    {
        static $mappings = [
            '@yearly' => '0 0 1 1 *',
            '@annually' => '0 0 1 1 *',
            '@monthly' => '0 0 1 * *',
            '@weekly' => '0 0 * * 0',
            '@daily' => '0 0 * * *',
            '@midnight' => '0 0 * * *',
            '@hourly' => '0 * * * *',
        ];

        $timezone ??= date_default_timezone_get();
        if (!$timezone instanceof DateTimeZone) {
            $timezone = new DateTimeZone($timezone);
        }

        return new self($mappings[$expression] ?? $expression, $timezone);
    }

    /**
     * Returns the Cron expression for running once a year, midnight, Jan. 1 - 0 0 1 1 *.
     */
    public static function yearly(DateTimeZone|string|null $timezone = null): self
    {
        return self::fromString('@yearly', $timezone);
    }

    /**
     * Returns the Cron expression for running once a month, midnight, first of month - 0 0 1 * *.
     */
    public static function monthly(DateTimeZone|string|null $timezone = null): self
    {
        return self::fromString('@monthly', $timezone);
    }

    /**
     * Returns the Cron expression for running once a week, midnight on Sun - 0 0 * * 0.
     */
    public static function weekly(DateTimeZone|string|null $timezone = null): self
    {
        return self::fromString('@weekly', $timezone);
    }

    /**
     * Returns the Cron expression for running once a day, midnight - 0 0 * * *.
     */
    public static function daily(DateTimeZone|string|null $timezone = null): self
    {
        return self::fromString('@daily', $timezone);
    }

    /**
     * Returns the Cron expression for running once an hour, first minute - 0 * * * *.
     */
    public static function hourly(DateTimeZone|string|null $timezone = null): self
    {
        return self::fromString('@hourly', $timezone);
    }

    /**
     * Validate a CronExpression.
     *
     * @see CronExpression::fromString
     */
    public static function isValid(string $expression): bool
    {
        try {
            self::fromString($expression);
        } catch (InvalidArgumentException $exception) {
            return false;
        }

        return true;
    }

    /**
     * Set max iteration count for searching next run dates.
     */
    public function setMaxIterationCount(int $maxIterationCount): CronExpression
    {
        $this->maxIterationCount = $maxIterationCount;

        return $this;
    }

    /**
     * Get a next run date relative to the current date or a specific date.
     *
     * @param DateTimeInterface|string|null $from    Relative calculation date
     * @param int                           $nth     Number of occurrences to skip before returning a
     *                                               matching next run date.  0, the default, will return the current
     *                                               date and time if the next run date falls on the current date and
     *                                               time.  Setting this value to 1 will skip the first match and go to
     *                                               the second match.  Setting this value to 2 will skip the first 2
     *                                               matches and so on.
     * @param int                           $options Set to self::ALLOW_CURRENT_DATE or self::DISALLOW_CURRENT_DATE to return or not
     *                                               the current date if it matches the cron expression
     *
     * @throws Exception        if the currentTime is invalid
     * @throws RuntimeException on too many iterations
     */
    public function nextRun(
        DateTimeInterface|string|null $from = 'now',
        int $nth = 0,
        int $options = self::DISALLOW_CURRENT_DATE
    ): DateTime {
        return $this->calculateRun($this->filterDate($from), $nth, false, $options);
    }

    /**
     * Get a previous run date relative to the current date or a specific date.
     *
     * @param DateTimeInterface|string|null $from    Relative calculation date
     * @param int                           $nth     Number of occurrences to skip before returning
     * @param int                           $options Set to self::ALLOW_CURRENT_DATE or self::DISALLOW_CURRENT_DATE to return or not
     *                                               the current date if it matches the cron expression
     *
     * @throws Exception        if the currentTime can not be resolved
     * @throws RuntimeException on too many iterations
     *
     * @see self::getNextRunDate
     */
    public function previousRun(
        DateTimeInterface|string|null $from = 'now',
        int $nth = 0,
        int $options = self::DISALLOW_CURRENT_DATE
    ): DateTime {
        return $this->calculateRun($this->filterDate($from), $nth, true, $options);
    }

    /**
     * Get multiple run dates starting at the current date or a specific date.
     *
     * @param int                           $total   Set the total number of dates to calculate
     * @param DateTimeInterface|string|null $from    Relative calculation date
     * @param int                           $options Set to self::ALLOW_CURRENT_DATE or self::DISALLOW_CURRENT_DATE to return or not
     *                                               the current date if it matches the cron expression
     *
     * @throws Exception        if the currentTime can not be resolved
     * @throws RuntimeException on too many iterations
     */
    public function nextOccurrences(
        int $total,
        DateTimeInterface|string|null $from = 'now',
        int $options = self::DISALLOW_CURRENT_DATE
    ): Generator {
        $currentDate = $this->filterDate($from);
        for ($i = 0; $i < max(0, $total); $i++) {
            try {
                yield $this->calculateRun($currentDate, $i, false, $options);
            } catch (RuntimeException $exception) {
                break;
            }
        }
    }

    /**
     * Get multiple run dates starting at the current date or a specific date.
     *
     * @param int                           $total   Set the total number of dates to calculate
     * @param DateTimeInterface|string|null $from    Relative calculation date
     * @param int                           $options Set to self::ALLOW_CURRENT_DATE or self::DISALLOW_CURRENT_DATE to return or not
     *                                               the current date if it matches the cron expression
     *
     * @throws Exception        if the currentTime can not be resolved
     * @throws RuntimeException on too many iterations
     */
    public function previousOccurrences(
        int $total,
        DateTimeInterface|string|null $from = 'now',
        int $options = self::DISALLOW_CURRENT_DATE
    ): Generator {
        $currentDate = $this->filterDate($from);
        for ($i = 0; $i < max(0, $total); $i++) {
            try {
                yield $this->calculateRun($currentDate, $i, true, $options);
            } catch (RuntimeException $exception) {
                break;
            }
        }
    }

    private function part(string|int $position): string|null
    {
        if (array_key_exists($position, $this->cronParts)) {
            return (string) $this->cronParts[$position];
        }

        return null;
    }

    public function minute(): string|null
    {
        return $this->part(self::MINUTE);
    }

    public function hour(): string|null
    {
        return $this->part(self::HOUR);
    }

    public function dayOfMonth(): string|null
    {
        return $this->part(self::MONTHDAY);
    }

    public function month(): string|null
    {
        return $this->part(self::MONTH);
    }

    public function dayOfWeek(): string|null
    {
        return $this->part(self::WEEKDAY);
    }

    public function toString(): string
    {
        return implode(' ', $this->cronParts);
    }

    /**
     * Helper method to output the full expression.
     *
     * @return string Full CRON expression
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Determine if the cron is due to run based on the current date or a
     * specific date.  This method assumes that the current number of
     * seconds are irrelevant, and should be called once per minute.
     *
     * @param DateTimeInterface|string $datetime Relative calculation date
     *
     * @throws Exception if the relative calculation date is invalid
     *
     * @return bool Returns TRUE if the cron is due to run or FALSE if not
     */
    public function isDue(DateTimeInterface|string $datetime = 'now'): bool
    {
        if ($datetime instanceof DateTimeInterface) {
            $currentDate = DateTime::createFromInterface($datetime);
            // Ensure time in 'current' timezone is used
            $currentDate->setTimezone($this->timezone);
        } else {
            $currentDate = new DateTime($datetime, $this->timezone);
        }

        $currentDate->setTime((int) $currentDate->format('H'), (int) $currentDate->format('i'), 0);
        try {
            return $this->nextRun($currentDate, 0, self::ALLOW_CURRENT_DATE) == $currentDate;
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * Get the next or previous run date of the expression relative to a date.
     *
     * @param DateTime $from             Relative calculation date
     * @param int      $nth              Number of matches to skip before returning
     * @param bool     $invert           Set to TRUE to go backwards in time
     * @param int      $allowCurrentDate Set to self::ALLOW_CURRENT_DATE or self::DISALLOW_CURRENT_DATE to return or not
     *                                   the current date if it matches the cron expression
     *
     * @throws RuntimeException on too many iterations
     */
    private function calculateRun(DateTime $from, int $nth, bool $invert, int $allowCurrentDate): DateTime
    {
        // We don't have to satisfy * or null fields
        $parts = [];
        $fields = [];
        foreach (self::TEST_ORDER_CRON_PARTS as $position) {
            $part = $this->part($position);
            if (null !== $part && '*' !== $part) {
                $parts[$position] = $part;
                $fields[$position] = self::field($position);
            }
        }

        // Set a hard limit to bail on an impossible date
        $nextRun = clone $from;
        for ($i = 0; $i < $this->maxIterationCount; $i++) {
            foreach ($parts as $position => $part) {
                $field = $fields[$position];
                // If the field is not satisfied, then start over
                if (!$this->isSatisfyingField($field, $part, $nextRun)) {
                    $field->increment($nextRun, $invert, $part);
                    continue 2;
                }
            }

            // Skip this match if needed
            if (($allowCurrentDate === self::DISALLOW_CURRENT_DATE && $nextRun == $from) || --$nth > -1) {
                self::field(0)->increment($nextRun, $invert, $parts[0] ?? null);
                continue;
            }

            return $nextRun;
        }

        // @codeCoverageIgnoreStart
        throw new RuntimeException('Impossible CRON expression');
        // @codeCoverageIgnoreEnd
    }

    /**
     * @throws Exception
     */
    private function filterDate(DateTimeInterface|string|null $currentTime): DateTime
    {
        if ($currentTime instanceof DateTimeInterface) {
            $currentDate = DateTime::createFromInterface($currentTime);
            $currentDate->setTimezone($this->timezone);
            $currentDate->setTime((int) $currentDate->format('H'), (int) $currentDate->format('i'), 0);

            return $currentDate;
        }

        $currentDate = new DateTime($currentTime ?? 'now', $this->timezone);
        $currentDate->setTime((int) $currentDate->format('H'), (int) $currentDate->format('i'), 0);

        return $currentDate;
    }

    private function isSatisfyingField(FieldInterface $field, string $part, DateTime $nextRun): bool
    {
        // Check if this is singular or a list
        if (!str_contains($part, ',')) {
            return $field->isSatisfiedBy($nextRun, $part);
        }

        foreach (array_map('trim', explode(',', $part)) as $listPart) {
            if ($field->isSatisfiedBy($nextRun, $listPart)) {
                return true;
            }
        }

        return false;
    }
}
