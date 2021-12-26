<?php

declare(strict_types=1);

namespace Bakame\Cron\Validator;

use DateInterval;
use DateTime;
use DateTimeInterface;

/**
 * Month field.  Allows: * , / -.
 */
final class Month extends Field
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

    public function isSatisfiedBy(DateTimeInterface $date, string $expression): bool
    {
        return $this->isSatisfied(
            (int) $date->format('m'),
            (string) $this->convertLiterals($expression)
        );
    }

    public function increment(DateTime $date, bool $invert = false, string $parts = null): DateTime
    {
        if ($invert) {
            return $date
                ->setDate((int) $date->format('Y'), (int) $date->format('n'), 1)
                ->sub(new DateInterval('P1D'))
                ->setTime(23, 59);
        }

        return $date
            ->setDate((int) $date->format('Y'), (int) $date->format('n') + 1, 1)
            ->setTime(0, 0);
    }
}
