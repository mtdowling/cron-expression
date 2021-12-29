<?php

namespace Bakame\Cron;

enum ExpressionField: string
{
    case MINUTE = 'minute';
    case HOUR = 'hour';
    case MONTHDAY = 'dayOfMonth';
    case MONTH = 'month';
    case WEEKDAY = 'dayOfWeek';

    /**
     * Get an instance of a field validator object for a cron expression position.
     */
    public function validator(): CronFieldValidator
    {
        return match ($this) {
            self::MINUTE => new MinuteValidator(),
            self::HOUR => new HourValidator(),
            self::MONTHDAY => new DayOfMonthValidator(),
            self::MONTH => new MonthValidator(),
            default => new DayOfWeekValidator(),
        };
    }

    public static function fromOffset(int $position): self
    {
        return match (true) {
            0 === $position => ExpressionField::MINUTE,
            1 === $position => ExpressionField::HOUR,
            2 === $position => ExpressionField::MONTHDAY,
            3 === $position => ExpressionField::MONTH,
            4 === $position => ExpressionField::WEEKDAY,
            default => throw SyntaxError::dueToInvalidPosition($position),
        };
    }

    /**
     * Order in which to test of cron parts.
     *
     * @return array<ExpressionField>
     */
    public static function orderedFields(): array
    {
        return [
            self::MONTH,
            self::MONTHDAY,
            self::WEEKDAY,
            self::HOUR,
            self::MINUTE,
        ];
    }
}
