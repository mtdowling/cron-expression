<?php

namespace Bakame\Cron;

use Bakame\Cron\Validator\DayOfMonth;
use Bakame\Cron\Validator\DayOfWeek;
use Bakame\Cron\Validator\FieldValidator;
use Bakame\Cron\Validator\Hours;
use Bakame\Cron\Validator\Minutes;
use Bakame\Cron\Validator\Month;
use Throwable;

final class ExpressionParser
{
    public const MINUTE = 0;
    public const HOUR = 1;
    public const MONTHDAY = 2;
    public const MONTH = 3;
    public const WEEKDAY = 4;

    /**
     * Get an instance of a field validator object for a cron expression position.
     *
     * @param int $fieldOffset CRON expression position value to retrieve
     *
     * @throws SyntaxError if a position is not valid
     */
    public static function validator(int $fieldOffset): FieldValidator
    {
        static $validators = [];

        $validators[$fieldOffset] ??= match ($fieldOffset) {
            self::MINUTE => new Minutes(),
            self::HOUR => new Hours(),
            self::MONTHDAY => new DayOfMonth(),
            self::MONTH => new Month(),
            self::WEEKDAY => new DayOfWeek(),
            default => throw SyntaxError::dueToInvalidPosition($fieldOffset),
        };

        return $validators[$fieldOffset];
    }

    /**
     * @return array<int, string>
     */
    public static function parse(string $expression): array
    {
        static $mappings = [
            '@yearly' => '0 0 1 1 *',
            '@annually' => '0 0 1 1 *',
            '@monthly' => '0 0 1 * *',
            '@weekly' => '0 0 * * 0',
            '@daily' => '0 0 * * *',
            '@midnight' => '0 0 * * *',
            '@hourly' => '0 * * * *',
        ];

        $expression = $mappings[$expression] ?? $expression;

        /** @var array<int, string> $fields */
        $fields = preg_split('/\s/', $expression, -1, PREG_SPLIT_NO_EMPTY);
        if (count($fields) < 5) {
            throw SyntaxError::dueToInvalidExpression($expression);
        }

        foreach ($fields as $position => $field) {
            if (!self::validator($position)->validate($field)) {
                throw SyntaxError::dueToInvalidFieldValue($field, $position);
            }
        }

        return $fields;
    }

    /**
     * Validate a CronExpression.
     *
     * @see CronExpression::filterFields
     */
    public static function isValid(string $expression): bool
    {
        try {
            self::parse($expression);
        } catch (Throwable) {
            return false;
        }

        return true;
    }
}
