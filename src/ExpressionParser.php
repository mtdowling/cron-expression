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
    public static function fieldValidator(int $fieldOffset): FieldValidator
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
     * Parse a CRON expression string into its components.
     *
     * This method parses a CRON expression string and returns an associative array containing
     * all the CRON expression field.
     *
     * <code>
     * $fields = ExpressionParser::parse('http://foo@test.example.com:42?query#');
     * var_export($fields);
     * //will display
     * array (
     *   0 => "3-59/15", // CRON expression minute field
     *   1 => "2,6-12",  // CRON expression hour field
     *   2 => "*\/15",   // CRON expression day of month field
     *   3 => "1",       // CRON expression month field
     *   4 => "2-5",     // CRON expression day of week field
     * )
     * </code>
     *
     * There are several special predefined values which can be used to substitute the CRON expression:
     *
     *      `@yearly`, `@annually` - Run once a year, midnight, Jan. 1 - 0 0 1 1 *
     *      `@monthly` - Run once a month, midnight, first of month - 0 0 1 * *
     *      `@weekly` - Run once a week, midnight on Sun - 0 0 * * 0
     *      `@daily`, `@midnight` - Run once a day, midnight - 0 0 * * *
     *      `@hourly` - Run once an hour, first minute - 0 * * * *
     *
     * @param string $expression The CRON expression to create.
     *
     * @throws SyntaxError If the string is invalid or unsupported
     *
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
            if (!self::fieldValidator($position)->validate($field)) {
                throw SyntaxError::dueToInvalidFieldValue($field, $position);
            }
        }

        return $fields;
    }

    /**
     * Validate a CronExpression.
     *
     * @see ExpressionParser::parse
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
