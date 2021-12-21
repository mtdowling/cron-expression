<?php

declare(strict_types=1);

namespace Cron;

use InvalidArgumentException;

/**
 * CRON field factory implementing a flyweight factory.
 * @link http://en.wikipedia.org/wiki/Cron
 */
class FieldFactory
{
    /**
     * @var array Cache of instantiated fields
     */
    private array $fields = [];

    /**
     * Get an instance of a field object for a cron expression position.
     *
     * @param int $position CRON expression position value to retrieve
     *
     * @throws InvalidArgumentException if a position is not valid
     */
    public function getField(int $position): FieldInterface
    {
        if (!isset($this->fields[$position])) {
            $this->fields[$position] = match ($position) {
                0 => new MinutesField(),
                1 => new HoursField(),
                2 => new DayOfMonthField(),
                3 => new MonthField(),
                4 => new DayOfWeekField(),
                default => throw new InvalidArgumentException($position.' is not a valid position'),
            };
        }

        return $this->fields[$position];
    }
}
