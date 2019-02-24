<?php

namespace Cron;

use InvalidArgumentException;

/**
 * CRON field factory implementing a flyweight factory
 * @link http://en.wikipedia.org/wiki/Cron
 */
class FieldFactory
{
    /**
     * @var array Cache of instantiated fields
     */
    private $fields = array();

    /**
     * Get an instance of a field object for a cron expression position
     *
     * @param int $position CRON expression position value to retrieve
     *
     * @return FieldInterface
     * @throws InvalidArgumentException if a position is not valid
     */
    public function getField($position)
    {
        return $this->fields[$position] ?? $this->fields[$position] = $this->instantiateField($position);
    }

    private function instantiateField($position): FieldInterface
    {
        switch ($position) {
            case CronExpression::MINUTE:
                return new MinutesField();
            case CronExpression::HOUR:
                return new HoursField();
            case CronExpression::DAY:
                return new DayOfMonthField();
            case CronExpression::MONTH:
                return new MonthField();
            case CronExpression::WEEKDAY:
                return new DayOfWeekField();
        }

        throw new InvalidArgumentException(
            ($position + 1) . ' is not a valid position'
        );
    }
}
