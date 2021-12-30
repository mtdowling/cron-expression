<?php

namespace Bakame\Cron;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Generator;
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
        DateTimeZone|string $timezone,
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

    private function filterTimezone(DateTimeZone|string $timezone): DateTimeZone
    {
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
        return new self($expression, new DateTimeZone(date_default_timezone_get()));
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
        if ($expression->toString() === $this->expression->toString()) {
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

    public function run(int $nth = 0, DateTimeInterface|string $startDate = 'now'): DateTimeImmutable
    {
        $invert = false;
        if (0 > $nth) {
            $nth = ($nth * -1) - 1;
            $invert = true;
        }

        return $this->calculateRun($nth, $startDate, $this->startDatePresence, $invert);
    }

    public function isDue(DateTimeInterface|string $when = 'now'): bool
    {
        try {
            return $this->calculateRun(0, $when, self::INCLUDE_START_DATE, false) == $this->toDateTimeImmutable($when);
        } catch (Throwable) {
            return false;
        }
    }

    public function yieldRunsForward(int $recurrences, DateTimeInterface|string $startDate = 'now'): Generator
    {
        $max = max(0, $recurrences);
        for ($i = 0; $i < $max; $i++) {
            try {
                yield $this->calculateRun($i, $startDate, $this->startDatePresence, false);
            } catch (UnableToProcessRun) {
                break;
            }
        }
    }

    public function yieldRunsBackward(int $recurrences, DateTimeInterface|string $startDate = 'now'): Generator
    {
        $max = max(0, $recurrences);
        for ($i = 0; $i < $max; $i++) {
            try {
                yield $this->calculateRun($i, $startDate, $this->startDatePresence, true);
            } catch (UnableToProcessRun) {
                break;
            }
        }
    }

    /**
     * Get the next or previous run date of the expression relative to a date.
     *
     * @param int $nth Number of matches to skip before returning
     * @param DateTimeInterface|string $startDate Relative calculation date
     * @param int $startDatePresence Set to self::INCLUDE_START_DATE to return the start date if eligible
     *                               Set to self::EXCLUDE_START_DATE to never return the start date
     * @param bool $invert Set to TRUE to go backwards in time
     *
     * @throws CronError on too many iterations
     */
    private function calculateRun(int $nth, DateTimeInterface|string $startDate, int $startDatePresence, bool $invert): DateTimeImmutable
    {
        $startDate = $this->toDateTimeImmutable($startDate);
        $calculatedFields = $this->calculatedFields();

        if (isset($calculatedFields[ExpressionField::MONTHDAY->value], $calculatedFields[ExpressionField::WEEKDAY->value])) {
            return $this->combineRuns($nth, $startDate, $invert);
        }

        // Set a hard limit to bail on an impossible date
        $nextRun = $startDate;
        for ($i = 0; $i < $this->maxIterationCount; $i++) {
            /**
             * @var string $fieldExpression
             * @var CronFieldValidator $fieldValidator
             */
            foreach ($calculatedFields as [$fieldExpression, $fieldValidator]) {
                // If the field is not satisfied, then start over
                if (!$this->isFieldSatisfiedBy($nextRun, $fieldValidator, $fieldExpression)) {
                    /** @var DateTimeImmutable $nextRun */
                    $nextRun = match ($invert) {
                        true => $fieldValidator->decrement($nextRun, $fieldExpression),
                        default => $fieldValidator->increment($nextRun, $fieldExpression),
                    };

                    continue 2;
                }
            }

            // Skip this match if needed
            if (($startDatePresence === self::EXCLUDE_START_DATE && $nextRun == $startDate) || --$nth > -1) {
                $fieldValidator = ExpressionField::MINUTE->validator();
                $fieldExpression = $calculatedFields[ExpressionField::MINUTE->value][0] ?? null;
                /** @var DateTimeImmutable $nextRun */
                $nextRun = match ($invert) {
                    true => $fieldValidator->decrement($nextRun, $fieldExpression),
                    default => $fieldValidator->increment($nextRun, $fieldExpression),
                };

                continue;
            }

            return $nextRun;
        }

        // @codeCoverageIgnoreStart
        throw UnableToProcessRun::dueToMaxIterationCountReached($this->maxIterationCount);
        // @codeCoverageIgnoreEnd
    }

    /**
     * @throws CronError
     */
    private function combineRuns(int $nth, DateTimeImmutable $startDate, bool $invert): DateTimeImmutable
    {
        $dayOfWeekScheduler = $this->withExpression($this->expression->withDayOfWeek('*'));
        $dayOfMonthScheduler = $this->withExpression($this->expression->withDayOfMonth('*'));

        $combinedRuns = match ($invert) {
            true => array_merge(
                iterator_to_array($dayOfMonthScheduler->yieldRunsBackward($nth + 1, $startDate), false),
                iterator_to_array($dayOfWeekScheduler->yieldRunsBackward($nth + 1, $startDate), false)
            ),
            default => array_merge(
                iterator_to_array($dayOfMonthScheduler->yieldRunsForward($nth + 1, $startDate), false),
                iterator_to_array($dayOfWeekScheduler->yieldRunsForward($nth + 1, $startDate), false)
            ),
        };

        usort($combinedRuns, fn (DateTimeInterface $a, DateTimeInterface $b): int => $a <=> $b);

        return $combinedRuns[$nth];
    }

    /**
     * @throws SyntaxError
     */
    private function toDateTimeImmutable(DateTimeInterface|string $date): DateTimeImmutable
    {
        try {
            $currentDate = match (true) {
                $date instanceof DateTimeImmutable => $date,
                $date instanceof DateTime => DateTimeImmutable::createFromInterface($date),
                default => new DateTimeImmutable($date),
            };
            $currentDate = $currentDate->setTimezone($this->timezone);

            return $currentDate->setTime((int) $currentDate->format('H'), (int) $currentDate->format('i'));
        } catch (Throwable $exception) {
            throw SyntaxError::dueToInvalidDate($date, $exception);
        }
    }

    private function isFieldSatisfiedBy(DateTimeInterface $dateTime, CronFieldValidator $fieldValidator, string $fieldExpression): bool
    {
        foreach (array_map('trim', explode(',', $fieldExpression)) as $expression) {
            if ($fieldValidator->isSatisfiedBy($expression, $dateTime)) {
                return true;
            }
        }

        return false;
    }

    private function calculatedFields(): array
    {
        // We don't have to satisfy * or null fields
        $fields = [];
        $expressionFields = $this->expression->fields();
        foreach (ExpressionField::orderedFields() as $field) {
            $fieldExpression = $expressionFields[$field->value];
            if ('*' !== $fieldExpression) {
                $fields[$field->value] = [$fieldExpression, $field->validator()];
            }
        }

        return $fields;
    }
}
