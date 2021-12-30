<?php

declare(strict_types=1);

namespace Bakame\Cron;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * Abstract CRON expression field.
 */
abstract class FieldValidator implements CronFieldValidator
{
    protected const RANGE_START = 0;
    protected const RANGE_END = 0;

    /** Literal values we need to convert to integers. */
    protected array $literals = [];

    /**
     * @return array<int>
     */
    final protected function fullRanges(): array
    {
        return range(static::RANGE_START, static::RANGE_END);
    }

    /**
     * Check to see if a field is satisfied by a value.
     */
    public function isSatisfied(int $dateValue, string $value): bool
    {
        return match (true) {
            $this->isIncrementsOfRanges($value) => $this->isInIncrementsOfRanges($dateValue, $value),
            $this->isRange($value) => $this->isInRange($dateValue, $value),
            default => $value === '*' || $dateValue === (int) $value,
        };
    }

    /**
     * Check if a value is a range.
     */
    protected function isRange(string $value): bool
    {
        return str_contains($value, '-');
    }

    /**
     * Check if a value is an increments of ranges.
     */
    protected function isIncrementsOfRanges(string $value): bool
    {
        return str_contains($value, '/');
    }

    /**
     * Test if a value is within a range.
     */
    protected function isInRange(int $dateValue, string $value): bool
    {
        $parts = array_map(fn (string $value): int => (int) $this->convertLiterals(trim($value)), explode('-', $value, 2));

        return $dateValue >= $parts[0] && $dateValue <= $parts[1];
    }

    /**
     * Test if a value is within an increments of ranges (offset[-to]/step size).
     */
    protected function isInIncrementsOfRanges(int $dateValue, string $value): bool
    {
        [$range, $step] = array_map('trim', explode('/', $value, 2)) + [1 => '0'];
        // No step or 0 steps aren't cool
        if ('0' === $step) {
            return false;
        }

        $step = (int) $step;
        // Expand the * to a full range
        if ('*' === $range) {
            $range = static::RANGE_START.'-'.static::RANGE_END;
        }

        // Generate the requested small range
        [$rangeStart, $rangeEnd] = explode('-', $range, 2) + [1 => null];
        $rangeStart = (int) $rangeStart;
        $rangeEnd = (int) ($rangeEnd ?? $rangeStart);

        if ($rangeStart < static::RANGE_START || $rangeStart > static::RANGE_END || $rangeStart > $rangeEnd) {
            throw RangeError::dueToInvalidInput('start');
        }

        if ($rangeEnd < static::RANGE_START || $rangeEnd > static::RANGE_END) {
            throw RangeError::dueToInvalidInput('end');
        }

        // Steps larger than the range need to wrap around and be handled slightly differently than smaller steps
        if ($step >= static::RANGE_END) {
            $fullRange = $this->fullRanges();

            return $dateValue === $fullRange[$step % count($fullRange)];
        }

        return in_array($dateValue, range($rangeStart, $rangeEnd, $step), true);
    }

    /**
     * Returns a range of values for the given cron expression.
     *
     * @return array<int>
     */
    protected function getRangeForExpression(string $expression, int $max): array
    {
        $expression = $this->convertLiterals($expression);
        if (str_contains($expression, ',')) {
            return array_reduce(
                explode(',', $expression),
                fn (array $values, string $range): array => array_merge($values, $this->getRangeForExpression($range, static::RANGE_END)),
                []
            );
        }

        if (!$this->isRange($expression) && !$this->isIncrementsOfRanges($expression)) {
            return [(int) $expression];
        }

        if (!$this->isIncrementsOfRanges($expression)) {
            [$offset, $to] = array_map([$this, 'convertLiterals'], explode('-', $expression));
            $step = 1;
        } else {
            [$range, $step] = explode('/', $expression, 2) + [1 => 0];
            [$offset, $to] = explode('-', (string) $range, 2) + [1 => $max];
        }

        $step = (int) $step;
        $offset = $offset === '*' ? 0 : $offset;
        if ($step >= static::RANGE_END) {
            $fullRange = $this->fullRanges();

            return [$fullRange[$step % count($fullRange)]];
        }

        return range((int) $offset, (int) $to, $step);
    }

    protected function convertLiterals(string $value): string
    {
        if ([] === $this->literals) {
            return $value;
        }

        $key = array_search(strtoupper($value), $this->literals, true);
        if ($key === false) {
            return $value;
        }

        return (string) $key;
    }

    public function isValid(string $fieldExpression): bool
    {
        $fieldExpression = $this->convertLiterals($fieldExpression);

        // All fields allow * as a valid value
        if ('*' === $fieldExpression) {
            return true;
        }

        if (str_contains($fieldExpression, '/')) {
            [$range, $step] = explode('/', $fieldExpression);

            return $this->isValid($range) && false !== filter_var($step, FILTER_VALIDATE_INT);
        }

        // Validate each chunk of a list individually
        if (str_contains($fieldExpression, ',')) {
            foreach (explode(',', $fieldExpression) as $listItem) {
                if (!$this->isValid($listItem)) {
                    return false;
                }
            }
            return true;
        }

        if (str_contains($fieldExpression, '-')) {
            if (substr_count($fieldExpression, '-') > 1) {
                return false;
            }

            [$first, $last] = array_map([$this, 'convertLiterals'], explode('-', $fieldExpression));
            if (in_array('*', [$first, $last], true)) {
                return false;
            }

            return $this->isValid($first) && $this->isValid($last);
        }

        return 1 === preg_match('/^\d+$/', $fieldExpression)
            && in_array((int) $fieldExpression, $this->fullRanges(), true);
    }

    protected function computePosition(int $currentValue, array $references, bool $invert): int
    {
        $nbField = count($references);
        $position = $invert ? $nbField - 1 : 0;
        if ($nbField <= 1) {
            return $position;
        }

        for ($i = 0; $i < $nbField - 1; $i++) {
            if ((!$invert && $currentValue >= $references[$i] && $currentValue < $references[$i + 1]) ||
                ($invert && $currentValue > $references[$i] && $currentValue <= $references[$i + 1])) {
                return $invert ? $i : $i + 1;
            }
        }

        return $position;
    }

    final protected function toDateTimeImmutable(DateTimeInterface $date): DateTimeImmutable
    {
        if (!$date instanceof DateTimeImmutable) {
            return DateTimeImmutable::createFromInterface($date);
        }

        return $date;
    }
}
