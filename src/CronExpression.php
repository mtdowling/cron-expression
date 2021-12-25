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
    /** @var array<int, string> CRON expression fields */
    private array $fields;
    private DateTimeZone $timezone;
    private int $maxIterationCount;

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
        int $nth = 0,
        DateTimeInterface|string $from = 'now',
        int $options = self::DISALLOW_CURRENT_DATE
    ): DateTimeImmutable {
        return $this->calculateRun($nth, $this->filterDate($from), $options, false);
    }

    public function previousRun(
        int $nth = 0,
        DateTimeInterface|string $from = 'now',
        int $options = self::DISALLOW_CURRENT_DATE
    ): DateTimeImmutable {
        return $this->calculateRun($nth, $this->filterDate($from), $options, true);
    }

    public function yieldNextRuns(
        int $total,
        DateTimeInterface|string $from = 'now',
        int $options = self::DISALLOW_CURRENT_DATE
    ): Generator {
        $currentDate = $this->filterDate($from);
        for ($i = 0; $i < max(0, $total); $i++) {
            try {
                yield $this->calculateRun($i, $currentDate, $options, false);
            } catch (RuntimeException) {
                break;
            }
        }
    }

    public function yieldPreviousRuns(
        int $total,
        DateTimeInterface|string $from = 'now',
        int $options = self::DISALLOW_CURRENT_DATE
    ): Generator {
        $currentDate = $this->filterDate($from);
        for ($i = 0; $i < max(0, $total); $i++) {
            try {
                yield $this->calculateRun($i, $currentDate, $options, true);
            } catch (RuntimeException) {
                break;
            }
        }
    }

    public function match(DateTimeInterface|string $datetime = 'now'): bool
    {
        $currentDate = $this->filterDate($datetime);
        try {
            return $this->nextRun(0, $currentDate, self::ALLOW_CURRENT_DATE) == $currentDate;
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
        return $this->fields[ExpressionParser::MINUTE];
    }

    public function hour(): string
    {
        return $this->fields[ExpressionParser::HOUR];
    }

    public function dayOfMonth(): string
    {
        return $this->fields[ExpressionParser::MONTHDAY];
    }

    public function month(): string
    {
        return $this->fields[ExpressionParser::MONTH];
    }

    public function dayOfWeek(): string
    {
        return $this->fields[ExpressionParser::WEEKDAY];
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
     * @param int $nth Number of matches to skip before returning
     * @param DateTime $from Relative calculation date
     * @param int $options Set to self::ALLOW_CURRENT_DATE or self::DISALLOW_CURRENT_DATE to return or not
     * @param bool $invert Set to TRUE to go backwards in time
     *                     the current date if it matches the cron expression
     *
     * @throws ExpressionError on too many iterations
     */
    private function calculateRun(int $nth, DateTime $from, int $options, bool $invert): DateTimeImmutable
    {
        $fields = $this->getOrderedFields();

        if (isset($fields[ExpressionParser::MONTHDAY], $fields[ExpressionParser::WEEKDAY])) {
            $combined = $this->getCombinedRuns($from, $nth, $options, $invert);

            usort($combined, fn (DateTimeInterface $a, DateTimeInterface $b): int => $a <=> $b);

            return DateTimeImmutable::createFromInterface($combined[$nth]);
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
            if (($options === self::DISALLOW_CURRENT_DATE && $nextRun == $from) || --$nth > -1) {
                $nextRun = ExpressionParser::fieldValidator(0)->increment($nextRun, $invert, $fields[0][0] ?? null);

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
        if ($currentTime instanceof DateTimeImmutable) {
            $currentDate = DateTime::createFromInterface($currentTime);
            $currentDate->setTimezone($this->timezone);
            $currentDate->setTime((int) $currentDate->format('H'), (int) $currentDate->format('i'));

            return $currentDate;
        }

        if ($currentTime instanceof DateTime) {
            $currentDate = clone $currentTime;
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

    /**
     * @throws ExpressionError
     * @return array<DateTimeInterface>
     */
    private function getCombinedRuns(DateTime $from, int $nth, int $options, bool $invert): array
    {
        $domExpression = new self(sprintf(
            '%s %s %s %s *',
            $this->fields[ExpressionParser::MINUTE],
            $this->fields[ExpressionParser::HOUR],
            $this->fields[ExpressionParser::MONTHDAY],
            $this->fields[ExpressionParser::MONTH]
        ), $this->timezone);

        $dowExpression = new self(sprintf(
            '%s %s * %s %s',
            $this->fields[ExpressionParser::MINUTE],
            $this->fields[ExpressionParser::HOUR],
            $this->fields[ExpressionParser::MONTH],
            $this->fields[ExpressionParser::WEEKDAY]
        ), $this->timezone);

        if ($invert) {
            return array_merge(
                iterator_to_array($domExpression->yieldPreviousRuns($nth + 1, $from, $options), false),
                iterator_to_array($dowExpression->yieldPreviousRuns($nth + 1, $from, $options), false),
            );
        }

        return array_merge(
            iterator_to_array($domExpression->yieldNextRuns($nth + 1, $from, $options), false),
            iterator_to_array($dowExpression->yieldNextRuns($nth + 1, $from, $options), false),
        );
    }

    private function getOrderedFields(): array
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
            $part = $this->fields[$position];
            if ('*' !== $part) {
                $fields[] = [$part, ExpressionParser::fieldValidator($position)];
            }
        }
        return $fields;
    }
}
