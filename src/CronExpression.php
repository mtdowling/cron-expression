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
    public const MINUTE = 0;
    public const HOUR = 1;
    public const MONTHDAY = 2;
    public const MONTH = 3;
    public const WEEKDAY = 4;
    public const YEAR = 5;

    /**
     * Order in which to test of cron parts.
     */
    private const TEST_ORDER_CRON_PARTS = [
        self::YEAR,
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
     * Parse a CRON expression.
     *
     * @param string       $expression   CRON expression (e.g. '8 * * * *')
     * @param FieldFactory $fieldFactory Factory to create cron fields
     */
    private function __construct(string $expression, private FieldFactory $fieldFactory)
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
        if (!$this->fieldFactory->getField($position)->validate($value)) {
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
    public static function fromString(string $expression, FieldFactory $fieldFactory = null): self
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

        return new self($mappings[$expression] ?? $expression, $fieldFactory ?? new FieldFactory());
    }

    /**
     * @deprecated
     * @see CronExpression::fromString
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
    public static function factory(string $expression, FieldFactory $fieldFactory = null): self
    {
        return self::fromString($expression, $fieldFactory ?? new FieldFactory());
    }

    public static function yearly(FieldFactory $fieldFactory = null): self
    {
        return self::fromString('@yearly', $fieldFactory ?? new FieldFactory());
    }

    public static function monthly(FieldFactory $fieldFactory = null): self
    {
        return self::fromString('@monthly', $fieldFactory ?? new FieldFactory());
    }

    public static function weekly(FieldFactory $fieldFactory = null): self
    {
        return self::fromString('@weekly', $fieldFactory ?? new FieldFactory());
    }

    public static function daily(FieldFactory $fieldFactory = null): self
    {
        return self::fromString('@daily', $fieldFactory ?? new FieldFactory());
    }

    public static function hourly(FieldFactory $fieldFactory = null): self
    {
        return self::fromString('@hourly', $fieldFactory ?? new FieldFactory());
    }

    /**
     * Validate a CronExpression.
     *
     * @see \Cron\CronExpression::fromString
     */
    public static function isValidExpression(string $expression): bool
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
     *
     * @param int $maxIterationCount Max iteration count when searching for next run date
     *
     */
    public function setMaxIterationCount(int $maxIterationCount): CronExpression
    {
        $this->maxIterationCount = $maxIterationCount;

        return $this;
    }

    /**
     * Get a next run date relative to the current date or a specific date.
     *
     * @param DateTimeInterface|string|null $currentTime      Relative calculation date
     * @param int                           $nth              Number of matches to skip before returning a
     *                                                        matching next run date.  0, the default, will return the current
     *                                                        date and time if the next run date falls on the current date and
     *                                                        time.  Setting this value to 1 will skip the first match and go to
     *                                                        the second match.  Setting this value to 2 will skip the first 2
     *                                                        matches and so on.
     * @param bool                          $allowCurrentDate Set to TRUE to return the current date if
     *                                                        it matches the cron expression.
     * @param null|string                   $timeZone         Timezone to use instead of the system default
     *
     * @throws Exception        if the currentTime is invalid
     * @throws RuntimeException on too many iterations
     */
    public function getNextRunDate(
        DateTimeInterface|string|null $currentTime = 'now',
        int $nth = 0,
        bool $allowCurrentDate = false,
        null|string $timeZone = null
    ): DateTime {
        return $this->getRunDate($this->filterDate($currentTime, $timeZone), $nth, false, $allowCurrentDate);
    }

    /**
     * Get a previous run date relative to the current date or a specific date.
     *
     * @param DateTimeInterface|string|null $currentTime      Relative calculation date
     * @param int                           $nth              Number of matches to skip before returning
     * @param bool                          $allowCurrentDate Set to TRUE to return the
     *                                                        current date if it matches the cron expression
     * @param null|string                   $timeZone         Timezone to use instead of the system default
     *
     * @throws Exception        if the currentTime can not be resolved
     * @throws RuntimeException on too many iterations
     *
     * @see self::getNextRunDate
     */
    public function getPreviousRunDate(
        DateTimeInterface|string|null $currentTime = 'now',
        int $nth = 0,
        bool $allowCurrentDate = false,
        null|string $timeZone = null
    ): DateTime {
        return $this->getRunDate($this->filterDate($currentTime, $timeZone), $nth, true, $allowCurrentDate);
    }

    /**
     * Get multiple run dates starting at the current date or a specific date.
     *
     * @param int                           $total            Set the total number of dates to calculate
     * @param DateTimeInterface|string|null $currentTime      Relative calculation date
     * @param bool                          $invert           Set to TRUE to retrieve previous dates
     * @param bool                          $allowCurrentDate Set to TRUE to return the
     *                                                        current date if it matches the cron expression
     * @param null|string                   $timeZone         Timezone to use instead of the system default
     *
     * @throws Exception        if the currentTime can not be resolved
     * @throws RuntimeException on too many iterations
     */
    public function getMultipleRunDates(
        int $total,
        DateTimeInterface|string|null $currentTime = 'now',
        bool $invert = false,
        bool $allowCurrentDate = false,
        null|string $timeZone = null
    ): Generator {
        $currentDate = $this->filterDate($currentTime, $timeZone);
        for ($i = 0; $i < max(0, $total); $i++) {
            try {
                yield $this->getRunDate($currentDate, $i, $invert, $allowCurrentDate);
            } catch (RuntimeException $exception) {
                break;
            }
        }
    }

    /**
     * @deprecated
     *
     * @see self::part
     * @see self::toString
     *
     * Get all or part of the CRON expression.
     *
     * @param string|int|null $part Specify the part to retrieve or NULL to get the full
     *                              cron schedule string.
     *
     * @return string|null Returns the CRON expression, a part of the
     *                     CRON expression, or NULL if the part was specified but not found
     */
    public function getExpression(string|int $part = null): string|null
    {
        if (null === $part) {
            return $this->toString();
        }

        return $this->part($part);
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
     * @param DateTimeInterface|string $currentTime Relative calculation date
     * @param null|string              $timeZone    Timezone to use instead of the system default
     *
     * @return bool Returns TRUE if the cron is due to run or FALSE if not
     */
    public function isDue(DateTimeInterface|string $currentTime = 'now', null|string $timeZone = null): bool
    {
        if ($currentTime instanceof DateTimeInterface) {
            $currentDate = DateTime::createFromInterface($currentTime);
            // Ensure time in 'current' timezone is used
            $currentDate->setTimezone(new DateTimeZone($timeZone ?? date_default_timezone_get()));
        } else {
            $currentDate = new DateTime($currentTime);
        }

        $currentDate->setTime((int) $currentDate->format('H'), (int) $currentDate->format('i'), 0);
        try {
            return $this->getNextRunDate($currentDate, 0, true) == $currentDate;
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * Get the next or previous run date of the expression relative to a date.
     *
     * @param DateTime $currentDate      Relative calculation date
     * @param int      $nth              Number of matches to skip before returning
     * @param bool     $invert           Set to TRUE to go backwards in time
     * @param bool     $allowCurrentDate Set to TRUE to return the
     *                                   current date if it matches the cron expression
     *
     * @throws RuntimeException on too many iterations
     */
    private function getRunDate(
        DateTime $currentDate,
        int $nth = 0,
        bool $invert = false,
        bool $allowCurrentDate = false
    ): DateTime {
        $nextRun = clone $currentDate;

        // We don't have to satisfy * or null fields
        $parts = [];
        $fields = [];
        foreach (self::TEST_ORDER_CRON_PARTS as $position) {
            $part = $this->part($position);
            if (null === $part || '*' === $part) {
                continue;
            }
            $parts[$position] = $part;
            $fields[$position] = $this->fieldFactory->getField($position);
        }

        // Set a hard limit to bail on an impossible date
        for ($i = 0; $i < $this->maxIterationCount; $i++) {
            foreach ($parts as $position => $part) {
                $satisfied = false;
                // Get the field object used to validate this part
                $field = $fields[$position];
                // Check if this is singular or a list
                if (!str_contains($part, ',')) {
                    $satisfied = $field->isSatisfiedBy($nextRun, $part);
                } else {
                    foreach (array_map('trim', explode(',', $part)) as $listPart) {
                        if ($field->isSatisfiedBy($nextRun, $listPart)) {
                            $satisfied = true;
                            break;
                        }
                    }
                }

                // If the field is not satisfied, then start over
                if (!$satisfied) {
                    $field->increment($nextRun, $invert, $part);
                    continue 2;
                }
            }

            // Skip this match if needed
            if ((!$allowCurrentDate && $nextRun == $currentDate) || --$nth > -1) {
                $this->fieldFactory->getField(0)->increment($nextRun, $invert, $parts[0] ?? null);
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
    private function filterDate(DateTimeInterface|string|null $currentTime, string|null $timeZone): DateTime
    {
        if ($currentTime instanceof DateTimeInterface) {
            $currentDate = DateTime::createFromInterface($currentTime);
            $currentDate->setTime((int) $currentDate->format('H'), (int) $currentDate->format('i'), 0);

            return $currentDate;
        }

        $currentDate = new DateTime($currentTime ?? 'now', new DateTimeZone($timeZone ?? date_default_timezone_get()));
        $currentDate->setTime((int) $currentDate->format('H'), (int) $currentDate->format('i'), 0);

        return $currentDate;
    }
}
