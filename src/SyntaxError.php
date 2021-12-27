<?php

namespace Bakame\Cron;

use DateTime;
use InvalidArgumentException;
use Throwable;

final class SyntaxError extends InvalidArgumentException implements ExpressionError
{
    public static function dueToInvalidPosition(int $position): self
    {
        return new self('`'.($position + 1).'` is not a valid CRON expression position.');
    }

    public static function dueToInvalidExpression(string $expression): self
    {
        return new self('`'.$expression.'` is not a valid CRON expression');
    }

    public static function dueToInvalidFieldValue(string $value, int $position): self
    {
        return new self('Invalid CRON field value '.$value.' at position '.$position);
    }

    public static function dueToInvalidDate(Throwable $exception): self
    {
        return new self('Invalid DateTime expression to instantiate a `'.DateTime::class.'`.', 0, $exception);
    }

    public static function dueToInvalidWeekday(int|string $nth): self
    {
        return new self("Hashed weekdays must be numeric, {$nth} given");
    }

    public static function dueToUnsupportedWeekday(int|string $weekday): self
    {
        return new self("Weekday must be a value between 0 and 7. {$weekday} given");
    }

    public static function dueToOutOfRangeWeekday(int $nth): self
    {
        return new self("There are never more than 5 or less than 1 of a given weekday in a month, {$nth} given");
    }

    public static function dueToInvalidMaxIterationCount(int $count): self
    {
        return new self("maxIterationCount must be an integer greater or equal to 0. {$count} given");
    }
}
