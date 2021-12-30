<?php

namespace Bakame\Cron;

use Throwable;

final class ExpressionParser
{
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
     *   'minute' => "3-59/15",   // CRON expression minute field
     *   'hour' => "2,6-12",      // CRON expression hour field
     *   'dayOfMonth' => "*\/15", // CRON expression day of month field
     *   'month' => "1",          // CRON expression month field
     *   'dayOfWeek' => "2-5",    // CRON expression day of week field
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
     * @return array<string, string>
     */
    public static function parse(string $expression): array
    {
        static $specialExpressions = [
            '@yearly' => '0 0 1 1 *',
            '@annually' => '0 0 1 1 *',
            '@monthly' => '0 0 1 * *',
            '@weekly' => '0 0 * * 0',
            '@daily' => '0 0 * * *',
            '@midnight' => '0 0 * * *',
            '@hourly' => '0 * * * *',
        ];

        $expression = $specialExpressions[strtolower($expression)] ?? $expression;
        /** @var array<int, string> $fields */
        $fields = preg_split('/\s/', $expression, -1, PREG_SPLIT_NO_EMPTY);
        if (count($fields) !== 5) {
            throw SyntaxError::dueToInvalidExpression($expression);
        }

        $errors = [];
        foreach ($fields as $position => $fieldExpression) {
            $offset = ExpressionField::fromOffset($position);
            if (!$offset->validator()->isValid($fieldExpression)) {
                $errors[$offset->value] = $fieldExpression;
            }
        }

        if ([] !== $errors) {
            throw SyntaxError::dueToInvalidFieldValue($errors);
        }

        return array_combine(array_map(fn (ExpressionField $field): string => $field->value, ExpressionField::cases()), $fields);
    }

    /**
     * Generate an CRON expression from its parsed representation returned by ExpressionParser::parse.
     *
     * If you supply your own array, you are responsible for providing
     * valid fields. If a required field is missing it will be replaced by the special `*` character
     *
     * @param array<string, string|int> $fields
     *
     * @throws SyntaxError If the fields array contains unknown or unsupported key fields
     */
    public static function build(array $fields): string
    {
        $defaultValues = [
            ExpressionField::MINUTE->value => '*',
            ExpressionField::HOUR->value => '*',
            ExpressionField::MONTHDAY->value => '*',
            ExpressionField::MONTH->value => '*',
            ExpressionField::WEEKDAY->value => '*',
        ];

        if ([] !== array_diff_key($fields, $defaultValues)) {
            throw new SyntaxError('The fields contain invalid offset names; expecting only the following fields: `'.implode('`, `', array_keys($defaultValues)).'`.');
        }

        $fields += $defaultValues;

        return implode(' ', [
            $fields[ExpressionField::MINUTE->value],
            $fields[ExpressionField::HOUR->value],
            $fields[ExpressionField::MONTHDAY->value],
            $fields[ExpressionField::MONTH->value],
            $fields[ExpressionField::WEEKDAY->value],
        ]);
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
