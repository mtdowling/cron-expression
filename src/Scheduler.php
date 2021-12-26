<?php

namespace Bakame\Cron;

use Bakame\Cron\Validator\FieldValidator;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Generator;
use RuntimeException;
use Throwable;

final class Scheduler
{
    public const EXCLUDE_START_DATE = 0;
    public const INCLUDE_START_DATE = 1;

    private DateTimeZone $timezone;
    private int $maxIterationCount;
    private int $options;

    public function __construct(
        private Expression $expression,
        DateTimeZone|string|null $timezone = null,
        int $maxIterationCount = 1000,
        int $options = self::EXCLUDE_START_DATE
    ) {
        $this->timezone = $this->filterTimezone($timezone);
        $this->maxIterationCount = $this->filterMaxIterationCount($maxIterationCount);
        $this->options = $this->filterOptions($options);
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

    private function filterOptions(int $options): int
    {
        if (!in_array($options, [self::EXCLUDE_START_DATE, self::INCLUDE_START_DATE], true)) {
            throw new SyntaxError('Unsupported or invalid options value.');
        }

        return $options;
    }

    public function expression(): Expression
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
        return self::EXCLUDE_START_DATE === $this->options;
    }

    public function withExpression(Expression $expression): self
    {
        if ($expression->toString() == $this->expression->toString()) {
            return $this;
        }

        $clone = clone $this;
        $clone->expression = $expression;

        return $clone;
    }

    public function withTimezone(DateTimeZone|string $timezone): self
    {
        $timezone = $this->filterTimezone($timezone);
        if ($timezone == $this->timezone) {
            return $this;
        }

        $clone = clone $this;
        $clone->timezone = $timezone;

        return $clone;
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

    public function includeStartDate(): self
    {
        if (self::INCLUDE_START_DATE === $this->options) {
            return $this;
        }

        $clone = clone $this;
        $clone->options = self::INCLUDE_START_DATE;

        return $clone;
    }

    public function excludeStartDate(): self
    {
        if (self::EXCLUDE_START_DATE === $this->options) {
            return $this;
        }

        $clone = clone $this;
        $clone->options = self::EXCLUDE_START_DATE;

        return $clone;
    }

    /**
     * Get a next run date relative to the current date or a specific date.
     *
     * @param int $nth Number of occurrences to skip before returning a
     *                 matching next run date.  0, the default, will return the current
     *                 date and time if the next run date falls on the current date and
     *                 time.  Setting this value to 1 will skip the first match and go to
     *                 the second match.  Setting this value to 2 will skip the first 2
     *                 matches and so on.
     * @param DateTimeInterface|string $relativeTo Relative calculation date
     *
     * @throws ExpressionError
     */
    public function run(int $nth = 0, DateTimeInterface|string $relativeTo = 'now'): DateTimeImmutable
    {
        $invert = false;
        if (0 > $nth) {
            $nth = ($nth * -1) - 1;
            $invert = true;
        }

        return $this->calculateRun($nth, $this->filterDate($relativeTo), $this->options, $invert);
    }

    /**
     * Determine if the cron is due to run based on the current date or a
     * specific date.  This method assumes that the current number of
     * seconds are irrelevant, and should be called once per minute.
     *
     * @param DateTimeInterface|string $dateTime Relative calculation date
     *
     * @throws ExpressionError
     */
    public function isDue(DateTimeInterface|string $dateTime = 'now'): bool
    {
        $currentDate = $this->filterDate($dateTime);
        try {
            return $this->calculateRun(0, $currentDate, self::INCLUDE_START_DATE, false) == $currentDate;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Get multiple run dates starting at the current date or a specific date.
     *
     * @param int $total Set the total number of dates to calculate
     * @param DateTimeInterface|string $relativeTo Relative calculation date
     *
     * @throws ExpressionError
     * @return Generator<DateTimeImmutable>
     */
    public function yieldRunsForward(int $total, DateTimeInterface|string $relativeTo = 'now'): Generator
    {
        $currentDate = $this->filterDate($relativeTo);
        for ($i = 0; $i < max(0, $total); $i++) {
            try {
                yield $this->calculateRun($i, $currentDate, $this->options, false);
            } catch (RuntimeException) {
                break;
            }
        }
    }

    /**
     * Get multiple run dates starting at the current date or a specific date.
     *
     * @param int $total Set the total number of dates to calculate
     * @param DateTimeInterface|string $relativeTo Relative calculation date
     *
     * @throws ExpressionError
     * @return Generator<DateTimeImmutable>
     *
     *
     * @see Scheduler::yieldRunsForward
     */
    public function yieldRunsBackward(int $total, DateTimeInterface|string $relativeTo = 'now'): Generator
    {
        $currentDate = $this->filterDate($relativeTo);
        for ($i = 0; $i < max(0, $total); $i++) {
            try {
                yield $this->calculateRun($i, $currentDate, $this->options, true);
            } catch (RuntimeException) {
                break;
            }
        }
    }

    /**
     * Get the next or previous run date of the expression relative to a date.
     *
     * @param int $nth Number of matches to skip before returning
     * @param DateTime $from Relative calculation date
     * @param bool $invert Set to TRUE to go backwards in time
     *                     the current date if it matches the cron expression
     *
     * @throws ExpressionError on too many iterations
     */
    private function calculateRun(int $nth, DateTime $from, int $options, bool $invert): DateTimeImmutable
    {
        $fields = $this->getOrderedFields();

        if (isset($fields[ExpressionParser::MONTHDAY], $fields[ExpressionParser::WEEKDAY])) {
            $combined = $this->getCombinedRuns($from, $nth, $invert);
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
            if (($options === self::EXCLUDE_START_DATE && $nextRun == $from) || --$nth > -1) {
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
     * @throws ExpressionError
     * @return array<DateTimeInterface>
     */
    private function getCombinedRuns(DateTime $from, int $nth, bool $invert): array
    {
        $domExpression = $this->expression->withDayOfMonth('*');
        $dowExpression = $this->expression->withDayOfWeek('*');


        if ($invert) {
            return array_merge(
                iterator_to_array($this->withExpression($domExpression)->yieldRunsBackward($nth + 1, $from), false),
                iterator_to_array($this->withExpression($dowExpression)->yieldRunsBackward($nth + 1, $from), false),
            );
        }

        return array_merge(
            iterator_to_array($this->withExpression($domExpression)->yieldRunsForward($nth + 1, $from), false),
            iterator_to_array($this->withExpression($dowExpression)->yieldRunsForward($nth + 1, $from), false),
        );
    }
    /**
     * @throws SyntaxError
     */
    private function filterDate(DateTimeInterface|string $date): DateTime
    {
        if ($date instanceof DateTimeImmutable) {
            $currentDate = DateTime::createFromInterface($date);
            $currentDate->setTimezone($this->timezone);
            $currentDate->setTime((int) $currentDate->format('H'), (int) $currentDate->format('i'));

            return $currentDate;
        }

        if ($date instanceof DateTime) {
            $currentDate = clone $date;
            $currentDate->setTimezone($this->timezone);
            $currentDate->setTime((int) $currentDate->format('H'), (int) $currentDate->format('i'));

            return $currentDate;
        }

        try {
            $currentDate = new DateTime($date, $this->timezone);
            $currentDate->setTime((int) $currentDate->format('H'), (int) $currentDate->format('i'));

            return $currentDate;
        } catch (Throwable $exception) {
            throw SyntaxError::dueToInvalidDate($date, $exception);
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
                $fields[] = [$part, ExpressionParser::fieldValidator($position)];
            }
        }

        return $fields;
    }
}
