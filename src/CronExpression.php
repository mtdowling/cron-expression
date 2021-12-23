<?php

declare(strict_types=1);

namespace Bakame\Cron;

use Bakame\Cron\Validator\FieldValidator;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Generator;
use JsonSerializable;
use RuntimeException;
use Stringable;
use Throwable;

final class CronExpression implements EditableExpression, JsonSerializable, Stringable
{
    /** @var array<int, int|string> CRON expression parts */
    private array $fields;
    private DateTimeZone $timezone;
    private int $maxIterationCount;

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
    public function __construct(string $expression, DateTimeZone|string|null $timezone = null, int $maxIterationCount = 1000)
    {
        $this->fields = ExpressionParser::parse($expression);
        $this->timezone = $this->filterTimezone($timezone);
        $this->maxIterationCount = $this->filterMaxIterationCount($maxIterationCount);
    }

    private function filterTimezone(DateTimeZone|string|null $timezone): DateTimeZone
    {
        $timezone ??= date_default_timezone_get();
        if (!$timezone instanceof DateTimeZone) {
            return new DateTimeZone($timezone);
        }

        return $timezone;
    }

    private function filterMaxIterationCount(int $maxIterationCount): int
    {
        if ($maxIterationCount < 0) {
            throw SyntaxError::dueToInvalidMaxIterationCount($maxIterationCount);
        }

        return $maxIterationCount;
    }

    /**
     * Returns the Cron expression for running once a year, midnight, Jan. 1 - 0 0 1 1 *.
     */
    public static function yearly(DateTimeZone|string|null $timezone = null, int $maxIterationCount = 1000): self
    {
        return new self('@yearly', $timezone, $maxIterationCount);
    }

    /**
     * Returns the Cron expression for running once a month, midnight, first of month - 0 0 1 * *.
     */
    public static function monthly(DateTimeZone|string|null $timezone = null, int $maxIterationCount = 1000): self
    {
        return new self('@monthly', $timezone, $maxIterationCount);
    }

    /**
     * Returns the Cron expression for running once a week, midnight on Sun - 0 0 * * 0.
     */
    public static function weekly(DateTimeZone|string|null $timezone = null, int $maxIterationCount = 1000): self
    {
        return new self('@weekly', $timezone, $maxIterationCount);
    }

    /**
     * Returns the Cron expression for running once a day, midnight - 0 0 * * *.
     */
    public static function daily(DateTimeZone|string|null $timezone = null, int $maxIterationCount = 1000): self
    {
        return new self('@daily', $timezone, $maxIterationCount);
    }

    /**
     * Returns the Cron expression for running once an hour, first minute - 0 * * * *.
     */
    public static function hourly(DateTimeZone|string|null $timezone = null, int $maxIterationCount = 1000): self
    {
        return new self('@hourly', $timezone, $maxIterationCount);
    }

    public function nextRun(
        DateTimeInterface|string $from = 'now',
        int $nth = 0,
        int $options = self::DISALLOW_CURRENT_DATE
    ): DateTimeImmutable {
        return $this->calculateRun($this->filterDate($from), $nth, $options, false);
    }

    public function previousRun(
        DateTimeInterface|string $from = 'now',
        int $nth = 0,
        int $options = self::DISALLOW_CURRENT_DATE
    ): DateTimeImmutable {
        return $this->calculateRun($this->filterDate($from), $nth, $options, true);
    }

    public function nextOccurrences(
        int $total,
        DateTimeInterface|string $from = 'now',
        int $options = self::DISALLOW_CURRENT_DATE
    ): Generator {
        $currentDate = $this->filterDate($from);
        for ($i = 0; $i < max(0, $total); $i++) {
            try {
                yield $this->calculateRun($currentDate, $i, $options, false);
            } catch (RuntimeException) {
                break;
            }
        }
    }

    public function previousOccurrences(
        int $total,
        DateTimeInterface|string $from = 'now',
        int $options = self::DISALLOW_CURRENT_DATE
    ): Generator {
        $currentDate = $this->filterDate($from);
        for ($i = 0; $i < max(0, $total); $i++) {
            try {
                yield $this->calculateRun($currentDate, $i, $options, true);
            } catch (RuntimeException) {
                break;
            }
        }
    }

    public function match(DateTimeInterface|string $datetime = 'now'): bool
    {
        $currentDate = $this->filterDate($datetime);
        try {
            return $this->nextRun($currentDate, 0, self::ALLOW_CURRENT_DATE) == $currentDate;
        } catch (Throwable) {
            return false;
        }
    }

    public function fields(): array
    {
        return $this->fields;
    }

    public function minute(): string
    {
        return (string) $this->fields[ExpressionParser::MINUTE];
    }

    public function hour(): string
    {
        return (string) $this->fields[ExpressionParser::HOUR];
    }

    public function dayOfMonth(): string
    {
        return (string) $this->fields[ExpressionParser::MONTHDAY];
    }

    public function month(): string
    {
        return (string) $this->fields[ExpressionParser::MONTH];
    }

    public function dayOfWeek(): string
    {
        return (string) $this->fields[ExpressionParser::WEEKDAY];
    }

    public function toString(): string
    {
        return implode(' ', $this->fields);
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function jsonSerialize(): string
    {
        return $this->toString();
    }

    public function timezone(): DateTimeZone
    {
        return $this->timezone;
    }

    public function maxIterationCount(): int
    {
        return $this->maxIterationCount;
    }

    public function withMinute(string $field): self
    {
        return $this->newInstance([ExpressionParser::MINUTE => $field] + $this->fields);
    }

    /**
     * @param array<int, string|int> $parts
     */
    private function newInstance(array $parts): self
    {
        ksort($parts);
        if ($parts === $this->fields) {
            return $this;
        }

        $clone = clone $this;
        $clone->fields = ExpressionParser::parse(implode(' ', $parts));

        return $clone;
    }

    public function withHour(string $field): self
    {
        return $this->newInstance([ExpressionParser::HOUR => $field] + $this->fields);
    }

    public function withDayOfMonth(string $field): self
    {
        return $this->newInstance([ExpressionParser::MONTHDAY => $field] + $this->fields);
    }

    public function withMonth(string $field): self
    {
        return $this->newInstance([ExpressionParser::MONTH => $field] + $this->fields);
    }

    public function withDayOfWeek(string $field): self
    {
        return $this->newInstance([ExpressionParser::WEEKDAY => $field] + $this->fields);
    }

    public function withTimezone(DateTimeZone|string $timezone): self
    {
        $timezone = $this->filterTimezone($timezone);
        if ($timezone == $this->timezone) {
            return $this;
        }

        return new self($this->toString(), $timezone);
    }

    public function withMaxIterationCount(int $maxIterationCount): self
    {
        if ($maxIterationCount === $this->maxIterationCount) {
            return $this;
        }

        $clone = clone $this;
        $clone->maxIterationCount = $this->filterMaxIterationCount($maxIterationCount);

        return $clone;
    }

    /**
     * Get the next or previous run date of the expression relative to a date.
     *
     * @param DateTimeInterface $from Relative calculation date
     * @param int $nth Number of matches to skip before returning
     * @param int $allowCurrentDate Set to self::ALLOW_CURRENT_DATE or self::DISALLOW_CURRENT_DATE to return or not
     * @param bool $invert Set to TRUE to go backwards in time
     *                     the current date if it matches the cron expression
     *
     * @throws UnableToProcessRun on too many iterations
     */
    private function calculateRun(DateTimeInterface $from, int $nth, int $allowCurrentDate, bool $invert): DateTimeImmutable
    {
        // Order in which to test of cron parts.
        static $testOrderCronFields = [
            ExpressionParser::MONTH,
            ExpressionParser::MONTHDAY,
            ExpressionParser::WEEKDAY,
            ExpressionParser::HOUR,
            ExpressionParser::MINUTE,
        ];

        // We don't have to satisfy * or null fields
        $fields = [];
        foreach ($testOrderCronFields as $position) {
            $part = (string) $this->fields[$position];
            if ('*' !== $part) {
                $fields[] = [$part, ExpressionParser::validator($position)];
            }
        }

        // Set a hard limit to bail on an impossible date
        $nextRun = clone $from;
        for ($i = 0; $i < $this->maxIterationCount; $i++) {
            foreach ($fields as [$part, $validator]) {
                // If the field is not satisfied, then start over
                if (!$this->isFieldSatisfiedBy($nextRun, $validator, $part)) {
                    $nextRun = $validator->increment($nextRun, $invert, $part);
                    continue 2;
                }
            }

            // Skip this match if needed
            if (($allowCurrentDate === self::DISALLOW_CURRENT_DATE && $nextRun == $from) || --$nth > -1) {
                $nextRun = ExpressionParser::validator(0)->increment($nextRun, $invert, $fields[0][0] ?? null);
                continue;
            }

            return DateTimeImmutable::createFromInterface($nextRun);
        }

        // @codeCoverageIgnoreStart
        throw UnableToProcessRun::dueToMaxIterationCountReached($this->maxIterationCount);
        // @codeCoverageIgnoreEnd
    }

    /**
     * @throws SyntaxError
     */
    private function filterDate(DateTimeInterface|string $currentTime): DateTime
    {
        if ($currentTime instanceof DateTimeInterface) {
            $currentDate = DateTime::createFromInterface($currentTime);
            $currentDate->setTimezone($this->timezone);
            $currentDate->setTime((int) $currentDate->format('H'), (int) $currentDate->format('i'));

            return $currentDate;
        }

        try {
            $currentDate = new DateTime($currentTime, $this->timezone);
            $currentDate->setTime((int) $currentDate->format('H'), (int) $currentDate->format('i'));

            return $currentDate;
        } catch (Throwable $exception) {
            throw SyntaxError::dueToInvalidDate($currentTime, $exception);
        }
    }

    private function isFieldSatisfiedBy(DateTimeInterface $dateTime, FieldValidator $field, string $part): bool
    {
        foreach (array_map('trim', explode(',', $part)) as $listPart) {
            if ($field->isSatisfiedBy($dateTime, $listPart)) {
                return true;
            }
        }

        return false;
    }
}
