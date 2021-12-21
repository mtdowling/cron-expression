<?php

declare(strict_types=1);

namespace Cron;

use DateTime;
use DateTimeInterface;

/**
 * Month field.  Allows: * , / -.
 */
class MonthField extends AbstractField
{
    protected int $rangeStart = 1;
    protected int $rangeEnd = 12;
    protected array $literals = [
        1 => 'JAN',
        2 => 'FEB',
        3 => 'MAR',
        4 => 'APR',
        5 => 'MAY',
        6 => 'JUN',
        7 => 'JUL',
        8 => 'AUG',
        9 => 'SEP',
        10 => 'OCT',
        11 => 'NOV',
        12 => 'DEC',
    ];

    public function isSatisfiedBy(DateTimeInterface $date, string $value): bool
    {
        return $this->isSatisfied(
            $date->format('m'),
            (string) $this->convertLiterals($value)
        );
    }

    public function increment(DateTime $date, bool $invert = false, string $parts = null): self
    {
        if ($invert) {
            $date->modify('last day of previous month');
            $date->setTime(23, 59);
        } else {
            $date->modify('first day of next month');
            $date->setTime(0, 0);
        }

        return $this;
    }
}
