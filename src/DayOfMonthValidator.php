<?php

declare(strict_types=1);

namespace Bakame\Cron;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * Day of month field.  Allows: * , / - ? L W.
 *
 * 'L' stands for "last" and specifies the last day of the month.
 *
 * The 'W' character is used to specify the weekday (Monday-Friday) nearest the
 * given day. As an example, if you were to specify "15W" as the value for the
 * day-of-month field, the meaning is: "the nearest weekday to the 15th of the
 * month". So if the 15th is a Saturday, the trigger will fire on Friday the
 * 14th. If the 15th is a Sunday, the trigger will fire on Monday the 16th. If
 * the 15th is a Tuesday, then it will fire on Tuesday the 15th. However if you
 * specify "1W" as the value for day-of-month, and the 1st is a Saturday, the
 * trigger will fire on Monday the 3rd, as it will not 'jump' over the boundary
 * of a month's days. The 'W' character can only be specified when the
 * day-of-month is a single day, not a range or list of days.
 *
 * @author Michael Dowling <mtdowling@gmail.com>
 */
final class DayOfMonthValidator extends FieldValidator
{
    protected int $rangeStart = 1;
    protected int $rangeEnd = 31;

    /**
     * Get the nearest day of the week for a given day in a month.
     *
     * @param int $currentYear Current year
     * @param int $currentMonth Current month
     * @param int $targetDay Target day of the month
     *
     * @return DateTime Returns the nearest date
     */
    private static function getNearestWeekday(int $currentYear, int $currentMonth, int $targetDay): DateTime
    {
        /** @var DateTime $target */
        $target = DateTime::createFromFormat('Y-n-j', "$currentYear-$currentMonth-$targetDay");
        if (6 > (int) $target->format('N')) {
            return $target;
        }

        $lastDayOfMonth = $target->format('t');
        foreach ([-1, 1, -2, 2] as $i) {
            $adjusted = $targetDay + $i;
            if ($adjusted > 0 && $adjusted <= $lastDayOfMonth) {
                $target->setDate($currentYear, $currentMonth, $adjusted);
                if (6 > (int) $target->format('N') && $target->format('m') == $currentMonth) {
                    return $target;
                }
            }
        }

        return $target;
    }

    public function isSatisfiedBy(DateTimeInterface $date, string $fieldExpression): bool
    {
        $fieldValue = $date->format('d');

        return match (true) {
            '?' === $fieldExpression => true,
            'L' === $fieldExpression => $fieldValue === $date->format('t'),
            false !== ($pos = strpos($fieldExpression, 'W')) => $date->format('j') === self::getNearestWeekday(
                (int) $date->format('Y'),
                (int) $date->format('m'),
                (int) substr($fieldExpression, 0, $pos) // Parse the target day
            )->format('j'),
            default => $this->isSatisfied((int) $date->format('d'), $fieldExpression),
        };
    }

    public function increment(DateTime|DateTimeImmutable $date, bool $invert = false, string $parts = null): DateTime|DateTimeImmutable
    {
        return match (true) {
            true === $invert => $date
                ->setDate((int) $date->format('Y'), (int) $date->format('n'), (int) $date->format('j') - 1)
                ->setTime(23, 59),
            default => $date
                ->setDate((int) $date->format('Y'), (int) $date->format('n'), (int) $date->format('j') + 1)
                ->setTime(0, 0),
        };
    }

    /**
     * @inheritDoc
     */
    public function isValid(string $fieldExpression): bool
    {
        return match (true) {
            str_contains($fieldExpression, ',') && (str_contains($fieldExpression, 'W') || str_contains($fieldExpression, 'L')) => false,
            true === parent::isValid($fieldExpression) => true,
            '?' === $fieldExpression => true,
            'L' === $fieldExpression => true,
            default => (1 === preg_match('/^(.*)W$/', $fieldExpression, $matches)) && $this->isValid($matches[1]),
        };
    }
}
