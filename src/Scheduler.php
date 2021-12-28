<?php

namespace Bakame\Cron;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Generator;
use RuntimeException;
use Throwable;

final class Scheduler implements CronScheduler
{
    public const EXCLUDE_START_DATE = 0;
    public const INCLUDE_START_DATE = 1;

    private CronExpression $expression;
    private DateTimeZone $timezone;
    private int $maxIterationCount;
    private int $startDatePresence;

    public function __construct(
        CronExpression|string $expression,
        DateTimeZone|string|null $timezone = null,
        int $startDatePresence = Scheduler::EXCLUDE_START_DATE,
        int $maxIterationCount = 1000
    ) {
        $this->expression = $this->filterExpression($expression);
        $this->timezone = $this->filterTimezone($timezone);
        $this->startDatePresence = $this->filterStartDatePresence($startDatePresence);
        $this->maxIterationCount = $this->filterMaxIterationCount($maxIterationCount);
    }

    private function filterExpression(CronExpression|string $expression): CronExpression
    {
        if (!$expression instanceof CronExpression) {
            return new Expression($expression);
        }

        return $expression;
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

    private function filterStartDatePresence(int $startDatePresence): int
    {
        if (!in_array($startDatePresence, [self::EXCLUDE_START_DATE, self::INCLUDE_START_DATE], true)) {
            throw SyntaxError::dueToInvalidStartDatePresence();
        }

        return $startDatePresence;
    }

    public static function fromUTC(CronExpression|string $expression): self
    {
        return new self($expression, new DateTimeZone('UTC'));
    }

    public static function fromSystemTimezone(CronExpression|string $expression): self
    {
        return new self($expression, date_default_timezone_get());
    }

    public function expression(): CronExpression
    {
        return $this->expression;
    }

    public function timezone(): DateTimeZone
    {
        return $this->timezone;
    }

    public function maxIterationCount(): int
    {
        return $this->maxIterationCount;
    }

    public function isStartDateExcluded(): bool
    {
        return self::EXCLUDE_START_DATE === $this->startDatePresence;
    }

    public function withExpression(CronExpression|string $expression): self
    {
        $expression = $this->filterExpression($expression);
        if ($expression->toString() == $this->expression->toString()) {
            return $this;
        }

        return new self($expression, $this->timezone, $this->startDatePresence, $this->maxIterationCount);
    }

    public function withTimezone(DateTimeZone|string $timezone): self
    {
        $timezone = $this->filterTimezone($timezone);
        if ($timezone->getName() === $this->timezone->getName()) {
            return $this;
        }

        return new self($this->expression, $timezone, $this->startDatePresence, $this->maxIterationCount);
    }

    public function withMaxIterationCount(int $maxIterationCount): self
    {
        if ($maxIterationCount === $this->maxIterationCount) {
            return $this;
        }

        return new self($this->expression, $this->timezone, $this->startDatePresence, $maxIterationCount);
    }

    public function includeStartDate(): self
    {
        if (self::INCLUDE_START_DATE === $this->startDatePresence) {
            return $this;
        }

        return new self($this->expression, $this->timezone, self::INCLUDE_START_DATE, $this->maxIterationCount);
    }

    public function excludeStartDate(): self
    {
        if (self::EXCLUDE_START_DATE === $this->startDatePresence) {
            return $this;
        }

        return new self($this->expression, $this->timezone, self::EXCLUDE_START_DATE, $this->maxIterationCount);
    }

    public function run(int $nth = 0, DateTimeInterface|string $relativeTo = 'now'): DateTimeImmutable
    {
        $invert = false;
        if (0 > $nth) {
            $invert = true;
            $nth *= -1;
            --$nth;
        }

        return $this->calculateRun($nth, $relativeTo, $this->startDatePresence, $invert);
    }

    public function isDue(DateTimeInterface|string $dateTime = 'now'): bool
    {
        try {
            return $this->calculateRun(0, $dateTime, self::INCLUDE_START_DATE, false) == $this->filterInputDate($dateTime);
        } catch (Throwable) {
            return false;
        }
    }

    public function yieldRunsForward(int $recurrences, DateTimeInterface|string $relativeTo = 'now'): Generator
    {
        for ($i = 0; $i < max(0, $recurrences); $i++) {
            try {
                yield $this->calculateRun($i, $relativeTo, $this->startDatePresence, false);
            } catch (RuntimeException) {
                break;
            }
        }
    }

    public function yieldRunsBackward(int $recurrences, DateTimeInterface|string $relativeTo = 'now'): Generator
    {
        for ($i = 0; $i < max(0, $recurrences); $i++) {
            try {
                yield $this->calculateRun($i, $relativeTo, $this->startDatePresence, true);
            } catch (RuntimeException) {
                break;
            }
        }
    }

    /**
     * Get the next or previous run date of the expression relative to a date.
     *
     * @param int $nth Number of matches to skip before returning
     * @param DateTimeInterface|string $relativeTo Relative calculation date
     * @param int $options Set to self::INCLUDE_START_DATE to return the current date if it matches the cron expression
     *                     Set to self::EXCLUDE_START_DATE to not return the current date if it matches the cron expression
     * @param bool $invert Set to TRUE to go backwards in time
     *
     * @throws CronError on too many iterations
     */
    private function calculateRun(int $nth, DateTimeInterface|string $relativeTo, int $options, bool $invert): DateTimeImmutable
    {
        $startDate = $this->filterInputDate($relativeTo);
        $fields = $this->getOrderedFields();

        if (isset($fields[ExpressionParser::MONTHDAY], $fields[ExpressionParser::WEEKDAY])) {
            return $this->combineRuns($nth, $startDate, $invert);
        }

        // Set a hard limit to bail on an impossible date
        $nextRun = clone $startDate;
        for ($i = 0; $i < $this->maxIterationCount; $i++) {
            /**
             * @var string $part
             * @var CronFieldValidator $validator
             */
            foreach ($fields as [$part, $validator]) {
                // If the field is not satisfied, then start over
                if (!$this->isFieldSatisfiedBy($nextRun, $validator, $part)) {
                    $nextRun = $validator->increment($nextRun, $invert, $part);

                    continue 2;
                }
            }

            // Skip this match if needed
            if (($options === self::EXCLUDE_START_DATE && $nextRun == $startDate) || --$nth > -1) {
                $nextRun = ExpressionParser::fieldValidator(0)->increment($nextRun, $invert, $fields[0][0] ?? null);

                continue;
            }

            return $this->formatOutputDate($nextRun, $relativeTo);
        }

        throw UnableToProcessRun::dueToMaxIterationCountReached($this->maxIterationCount);
    }

    /**
     * @throws CronError
     */
    private function combineRuns(int $nth, DateTime $relativeTo, bool $invert): DateTimeImmutable
    {
        $dayOfWeekScheduler = $this->withExpression($this->expression->withDayOfWeek('*'));
        $dayOfMonthScheduler = $this->withExpression($this->expression->withDayOfMonth('*'));

        $combinedArray = match (true) {
            $invert === true => array_merge(
                iterator_to_array($dayOfMonthScheduler->yieldRunsBackward($nth + 1, $relativeTo), false),
                iterator_to_array($dayOfWeekScheduler->yieldRunsBackward($nth + 1, $relativeTo), false)
            ),
            default => array_merge(
                iterator_to_array($dayOfMonthScheduler->yieldRunsForward($nth + 1, $relativeTo), false),
                iterator_to_array($dayOfWeekScheduler->yieldRunsForward($nth + 1, $relativeTo), false)
            ),
        };

        usort($combinedArray, fn (DateTimeInterface $a, DateTimeInterface $b): int => $a <=> $b);

        return $combinedArray[$nth];
    }

    /**
     * @throws SyntaxError
     */
    private function filterInputDate(DateTimeInterface|string $date): DateTime
    {
        try {
            $currentDate = match (true) {
                $date instanceof DateTimeImmutable => DateTime::createFromInterface($date),
                $date instanceof DateTime => clone $date,
                default => new DateTime($date),
            };
            $currentDate->setTimezone($this->timezone);
            $currentDate->setTime((int) $currentDate->format('H'), (int) $currentDate->format('i'));

            return $currentDate;
        } catch (Throwable $exception) {
            throw SyntaxError::dueToInvalidDate($date, $exception);
        }
    }

    private function formatOutputDate(DateTimeInterface $resultDate, DateTimeInterface|string $inputDate): DateTimeImmutable
    {
        return match (true) {
            $inputDate instanceof DateTimeImmutable => $inputDate::createFromInterface($resultDate)->setTimezone($this->timezone),
            default => DateTimeImmutable::createFromInterface($resultDate)->setTimezone($this->timezone)
        };
    }

    private function isFieldSatisfiedBy(DateTimeInterface $dateTime, CronFieldValidator $field, string $part): bool
    {
        foreach (array_map('trim', explode(',', $part)) as $listPart) {
            if ($field->isSatisfiedBy($dateTime, $listPart)) {
                return true;
            }
        }

        return false;
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
        $expressionFields = $this->expression->fields();
        foreach ($testOrderCronFields as $position) {
            $part = $expressionFields[$position];
            if ('*' !== $part) {
                $fields[$position] = [$part, ExpressionParser::fieldValidator($position)];
            }
        }

        return $fields;
    }
}
