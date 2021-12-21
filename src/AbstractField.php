<?php

declare(strict_types=1);

namespace Cron;

use OutOfRangeException;

/**
 * Abstract CRON expression field.
 */
abstract class AbstractField implements FieldInterface
{
    /**
     * Full range of values that are allowed for this field type.
     */
    protected array $fullRange = [];

    /**
     * Literal values we need to convert to integers.
     */
    protected array $literals = [];

    /**
     * Start value of the full range.
     */
    protected int $rangeStart;

    /**
     * End value of the full range.
     */
    protected int $rangeEnd;


    public function __construct()
    {
        $this->fullRange = range($this->rangeStart, $this->rangeEnd);
    }

    /**
     * Check to see if a field is satisfied by a value.
     */
    public function isSatisfied(string $dateValue, string $value): bool
    {
        if ($this->isIncrementsOfRanges($value)) {
            return $this->isInIncrementsOfRanges($dateValue, $value);
        }

        if ($this->isRange($value)) {
            return $this->isInRange($dateValue, $value);
        }

        return $value == '*' || $dateValue == $value;
    }

    /**
     * Check if a value is a range.
     */
    public function isRange(string $value): bool
    {
        return str_contains($value, '-');
    }

    /**
     * Check if a value is an increments of ranges.
     */
    public function isIncrementsOfRanges(string $value): bool
    {
        return str_contains($value, '/');
    }

    /**
     * Test if a value is within a range.
     */
    public function isInRange(string $dateValue, string $value): bool
    {
        $parts = array_map('trim', explode('-', $value, 2));

        return $dateValue >= $parts[0] && $dateValue <= $parts[1];
    }

    /**
     * Test if a value is within an increments of ranges (offset[-to]/step size).
     */
    public function isInIncrementsOfRanges(string $dateValue, string $value): bool
    {
        $chunks = array_map('trim', explode('/', $value, 2));
        $range = $chunks[0];
        $step = $chunks[1] ?? 0;

        // No step or 0 steps aren't cool
        if (is_null($step) || '0' === $step || 0 === $step) {
            return false;
        }

        $step = (int) $step;

        // Expand the * to a full range
        if ('*' == $range) {
            $range = $this->rangeStart.'-'.$this->rangeEnd;
        }

        // Generate the requested small range
        $rangeChunks = explode('-', $range, 2);
        $rangeStart = (int) $rangeChunks[0];
        $rangeEnd = (int) ($rangeChunks[1] ?? $rangeStart);

        if ($rangeStart < $this->rangeStart || $rangeStart > $this->rangeEnd || $rangeStart > $rangeEnd) {
            throw new OutOfRangeException('Invalid range start requested');
        }

        if ($rangeEnd < $this->rangeStart || $rangeEnd > $this->rangeEnd || $rangeEnd < $rangeStart) {
            throw new OutOfRangeException('Invalid range end requested');
        }

        if ($step > ($rangeEnd - $rangeStart) + 1) {
            throw new OutOfRangeException('Step cannot be greater than total range');
        }

        return in_array($dateValue, range($rangeStart, $rangeEnd, $step));
    }

    /**
     * Returns a range of values for the given cron expression.
     *
     * @return array<int>
     */
    public function getRangeForExpression(string $expression, int $max): array
    {
        $values = [];

        if ($this->isRange($expression) || $this->isIncrementsOfRanges($expression)) {
            if (!$this->isIncrementsOfRanges($expression)) {
                list($offset, $to) = explode('-', $expression);
                $stepSize = 1;
            } else {
                $range = array_map(fn ($value): int => (int) trim($value), explode('/', $expression, 2));
                $stepSize = $range[1] ?? 0;
                $range = $range[0];
                $range = explode('-', (string) $range, 2);
                $offset = $range[0];
                $to = $range[1] ?? $max;
            }
            $offset = $offset == '*' ? 0 : $offset;
            for ($i = $offset; $i <= $to; $i += $stepSize) {
                $values[] = $i;
            }
            sort($values);
        } else {
            $values = [(int) $expression];
        }

        return $values;
    }

    protected function convertLiterals(string $value): int|string
    {
        if (count($this->literals)) {
            $key = array_search($value, $this->literals);
            if ($key !== false) {
                return $key;
            }
        }

        return $value;
    }

    /**
     * Checks to see if a value is valid for the field.
     */
    public function validate(string $value): bool
    {
        $value = (string) $this->convertLiterals($value);

        // All fields allow * as a valid value
        if ('*' === $value) {
            return true;
        }

        // You cannot have a range and a list at the same time
        if (str_contains($value, ',') && str_contains($value, '-')) {
            return false;
        }

        if (str_contains($value, '/')) {
            list($range, $step) = explode('/', $value);
            return $this->validate($range) && filter_var($step, FILTER_VALIDATE_INT);
        }

        if (str_contains($value, '-')) {
            if (substr_count($value, '-') > 1) {
                return false;
            }

            $chunks = explode('-', $value);
            $chunks[0] = $this->convertLiterals($chunks[0]);
            $chunks[1] = $this->convertLiterals($chunks[1]);

            if ('*' == $chunks[0] || '*' == $chunks[1]) {
                return false;
            }

            return $this->validate((string) $chunks[0]) && $this->validate((string) $chunks[1]);
        }

        // Validate each chunk of a list individually
        if (str_contains($value, ',')) {
            foreach (explode(',', $value) as $listItem) {
                if (!$this->validate($listItem)) {
                    return false;
                }
            }
            return true;
        }

        // We should have a numeric by now, so coerce this into an integer
        if (filter_var($value, FILTER_VALIDATE_INT) !== false) {
            $value = (int) $value;
        }

        return in_array($value, $this->fullRange, true);
    }
}
