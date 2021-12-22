<?php

declare(strict_types=1);

namespace Cron;

use DateTime;
use DateTimeInterface;

/**
 * Day of week field.  Allows: * / , - ? L #.
 *
 * Days of the week can be represented as a number 0-7 (0|7 = Sunday)
 * or as a three letter string: SUN, MON, TUE, WED, THU, FRI, SAT.
 *
 * 'L' stands for "last". It allows you to specify constructs such as
 * "the last Friday" of a given month.
 *
 * '#' is allowed for the day-of-week field, and must be followed by a
 * number between one and five. It allows you to specify constructs such as
 * "the second Friday" of a given month.
 */
final class DayOfWeekField extends AbstractField
{
    protected int $rangeStart = 0;
    protected int $rangeEnd = 7;
    protected array $nthRange;
    protected array $literals = [
        1 => 'MON',
        2 => 'TUE',
        3 => 'WED',
        4 => 'THU',
        5 => 'FRI',
        6 => 'SAT',
        7 => 'SUN',
    ];

    public function __construct()
    {
        $this->nthRange = range(1, 5);
        parent::__construct();
    }

    public function isSatisfiedBy(DateTimeInterface $date, string $expression): bool
    {
        if ($expression == '?') {
            return true;
        }

        // Convert text day of the week values to integers
        $expression = (string) $this->convertLiterals($expression);

        $currentYear = (int) $date->format('Y');
        $currentMonth = (int) $date->format('m');
        $lastDayOfMonth = (int) $date->format('t');

        // Find out if this is the last specific weekday of the month
        $pos = strpos($expression, 'L');
        if (false !== $pos) {
            $weekday = str_replace('7', '0', substr($expression, 0, $pos));
            $tempDate = DateTime::createFromInterface($date);
            $tempDate->setDate($currentYear, $currentMonth, $lastDayOfMonth);
            while ($tempDate->format('w') !== $weekday) {
                $tempDate->setDate($currentYear, $currentMonth, --$lastDayOfMonth);
            }

            return $date->format('j') == $lastDayOfMonth;
        }

        // Handle # hash tokens
        if (str_contains($expression, '#')) {
            [$weekday, $nth] = explode('#', $expression);

            if (!is_numeric($nth)) {
                throw SyntaxError::dueToInvalidWeekday($nth);
            }

            $nth = (int) $nth;
            // 0 and 7 are both Sunday, however 7 matches date('N') format ISO-8601
            if ($weekday === '0') {
                $weekday = 7;
            }

            $weekday = $this->convertLiterals((string) $weekday);

            // Validate the hash fields
            if ($weekday < 0 || $weekday > 7) {
                throw SyntaxError::dueToUnsupportedWeekday($weekday);
            }

            if (!in_array($nth, $this->nthRange, true)) {
                throw SyntaxError::dueToOutOfRangeWeekday($nth);
            }

            // The current weekday must match the targeted weekday to proceed
            if ($date->format('N') != $weekday) {
                return false;
            }

            $tempDate = DateTime::createFromInterface($date);
            $tempDate->setDate($currentYear, $currentMonth, 1);
            $dayCount = 0;
            $currentDay = 1;
            while ($currentDay < $lastDayOfMonth + 1) {
                if ($tempDate->format('N') == $weekday) {
                    if (++$dayCount >= $nth) {
                        break;
                    }
                }
                $tempDate->setDate($currentYear, $currentMonth, ++$currentDay);
            }

            return $date->format('j') == $currentDay;
        }

        // Handle day of the week values
        if (str_contains($expression, '-')) {
            $parts = explode('-', $expression);
            if ($parts[0] == '7') {
                $parts[0] = '0';
            } elseif ($parts[1] == '0') {
                $parts[1] = '7';
            }
            $expression = implode('-', $parts);
        }

        // Test to see which Sunday to use -- 0 == 7 == Sunday
        $format = in_array('7', str_split($expression), true) ? 'N' : 'w';
        $fieldValue = $date->format($format);

        return $this->isSatisfied($fieldValue, $expression);
    }

    public function increment(DateTime $date, bool $invert = false, string $parts = null): void
    {
        if ($invert) {
            $date->modify('-1 day');
            $date->setTime(23, 59, 0);

            return;
        }

        $date->modify('+1 day');
        $date->setTime(0, 0, 0);
    }

    /**
     * @inheritDoc
     */
    public function validate(string $expression): bool
    {
        if (true === parent::validate($expression)) {
            return true;
        }

        // Handle the # value
        if (str_contains($expression, '#')) {
            $chunks = explode('#', $expression);
            $chunks[0] = (string) $this->convertLiterals($chunks[0]);
            if (parent::validate($chunks[0]) && is_numeric($chunks[1]) && in_array((int) $chunks[1], $this->nthRange, true)) {
                return true;
            }
        }

        if (1 !== preg_match('/^(.*)L$/', $expression, $matches)) {
            return false;
        }

        return $this->validate($matches[1]);
    }
}
