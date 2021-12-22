<?php

declare(strict_types=1);

namespace Cron;

/**
 * Abstract CRON expression field.
 */
abstract class AbstractField implements Field
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
    public function isSatisfied(int $dateValue, string $value): bool
    {
        if ($this->isIncrementsOfRanges($value)) {
            return $this->isInIncrementsOfRanges($dateValue, $value);
        }

        if ($this->isRange($value)) {
            return $this->isInRange($dateValue, $value);
        }

        return $value === '*' || $dateValue == (int) $value;
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
    public function isInRange(int $dateValue, string $value): bool
    {
        $parts = array_map(fn (string $value): int => (int) $this->convertLiterals(trim($value)), explode('-', $value, 2));

        return $dateValue >= $parts[0] && $dateValue <= $parts[1];
    }

    /**
     * Test if a value is within an increments of ranges (offset[-to]/step size).
     */
    public function isInIncrementsOfRanges(int $dateValue, string $value): bool
    {
        $chunks = array_map('trim', explode('/', $value, 2));
        $range = $chunks[0];
        $step = (int) ($chunks[1] ?? 0);

        // No step or 0 steps aren't cool
        if (in_array($step, ['0', 0], true)) {
            return false;
        }

        // Expand the * to a full range
        if ('*' === $range) {
            $range = $this->rangeStart.'-'.$this->rangeEnd;
        }

        // Generate the requested small range
        $rangeChunks = explode('-', $range, 2);
        $rangeStart = (int) $rangeChunks[0];
        $rangeEnd = (int) ($rangeChunks[1] ?? $rangeStart);

        if ($rangeStart < $this->rangeStart || $rangeStart > $this->rangeEnd || $rangeStart > $rangeEnd) {
            throw RangeError::dueToInvalidInput('start');
        }

        if ($rangeEnd < $this->rangeStart || $rangeEnd > $this->rangeEnd || $rangeEnd < $rangeStart) {
            throw RangeError::dueToInvalidInput('end');
        }

        // Steps larger than the range need to wrap around and be handled slightly differently than smaller steps
        if ($step >= $this->rangeEnd) {
            return $dateValue === $this->fullRange[$step % count($this->fullRange)];
        }

        return in_array($dateValue, range($rangeStart, $rangeEnd, $step), true);
    }

    /**
     * Returns a range of values for the given cron expression.
     *
     * @return array<int|string>
     */
    public function getRangeForExpression(string $expression, int $max): array
    {
        $values = [];
        $expression = (string) $this->convertLiterals($expression);
        if (str_contains($expression, ',')) {
            $ranges = explode(',', $expression);
            foreach ($ranges as $range) {
                $values = array_merge($values, $this->getRangeForExpression($range, $this->rangeEnd));
            }

            return $values;
        }

        if ($this->isRange($expression) || $this->isIncrementsOfRanges($expression)) {
            if (!$this->isIncrementsOfRanges($expression)) {
                [$offset, $to] = explode('-', $expression);
                $offset = $this->convertLiterals($offset);
                $to = $this->convertLiterals($to);
                $stepSize = 1;
            } else {
                $range = explode('/', $expression, 2);
                $stepSize = $range[1] ?? 0;
                $range = $range[0];
                $range = explode('-', $range, 2);
                $offset = $range[0];
                $to = $range[1] ?? $max;
            }
            $offset = $offset == '*' ? 0 : $offset;
            if ($stepSize >= $this->rangeEnd) {
                $values = [(int) $this->fullRange[$stepSize % count($this->fullRange)]];
            } else {
                for ($i = $offset; $i <= $to; $i += $stepSize) {
                    $values[] = (int) $i;
                }
            }
            sort($values);
        } else {
            $values = [$expression];
        }

        return $values;
    }

    protected function convertLiterals(string $value): int|string
    {
        if ([] === $this->literals) {
            return $value;
        }

        $key = array_search(strtoupper($value), $this->literals, true);
        if ($key !== false) {
            return $key;
        }

        return $value;
    }

    /**
     * Checks to see if a value is valid for the field.
     */
    public function validate(string $expression): bool
    {
        $expression = (string) $this->convertLiterals($expression);

        // All fields allow * as a valid value
        if ('*' === $expression) {
            return true;
        }

        if (str_contains($expression, '/')) {
            [$range, $step] = explode('/', $expression);

            return $this->validate($range) && false !== filter_var($step, FILTER_VALIDATE_INT);
        }

        // Validate each chunk of a list individually
        if (str_contains($expression, ',')) {
            foreach (explode(',', $expression) as $listItem) {
                if (!$this->validate($listItem)) {
                    return false;
                }
            }
            return true;
        }

        if (str_contains($expression, '-')) {
            if (substr_count($expression, '-') > 1) {
                return false;
            }

            [$first, $last] = explode('-', $expression);
            $first = $this->convertLiterals($first);
            $last = $this->convertLiterals($last);

            if (in_array('*', [$first, $last], true)) {
                return false;
            }

            return $this->validate((string) $first) && $this->validate((string) $last);
        }

        if (!is_numeric($expression)) {
            return false;
        }

        if (str_contains($expression, '.')) {
            return false;
        }

        return in_array((int) $expression, $this->fullRange, true);
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
}
