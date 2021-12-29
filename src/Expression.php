<?php

declare(strict_types=1);

namespace Bakame\Cron;

use JsonSerializable;
use Stringable;

final class Expression implements CronExpression, JsonSerializable, Stringable
{
    /** @var array<string, string> CRON expression fields */
    private array $fields;

    public function __construct(string $expression)
    {
        $this->fields = ExpressionParser::parse($expression);
    }

    /**
     * Returns the Cron expression for running once a year, midnight, Jan. 1 - 0 0 1 1 *.
     */
    public static function yearly(): self
    {
        return new self('@yearly');
    }

    /**
     * Returns the Cron expression for running once a month, midnight, first of month - 0 0 1 * *.
     */
    public static function monthly(): self
    {
        return new self('@monthly');
    }

    /**
     * Returns the Cron expression for running once a week, midnight on Sun - 0 0 * * 0.
     */
    public static function weekly(): self
    {
        return new self('@weekly');
    }

    /**
     * Returns the Cron expression for running once a day, midnight - 0 0 * * *.
     */
    public static function daily(): self
    {
        return new self('@daily');
    }

    /**
     * Returns the Cron expression for running once an hour, first minute - 0 * * * *.
     */
    public static function hourly(): self
    {
        return new self('@hourly');
    }

    public function fields(): array
    {
        return $this->fields;
    }

    public function minute(): string
    {
        return $this->fields[ExpressionField::MINUTE->value];
    }

    public function hour(): string
    {
        return $this->fields[ExpressionField::HOUR->value];
    }

    public function dayOfMonth(): string
    {
        return $this->fields[ExpressionField::MONTHDAY->value];
    }

    public function month(): string
    {
        return $this->fields[ExpressionField::MONTH->value];
    }

    public function dayOfWeek(): string
    {
        return $this->fields[ExpressionField::WEEKDAY->value];
    }

    public function toString(): string
    {
        return implode(' ', $this->fields);
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function jsonSerialize(): string
    {
        return $this->toString();
    }

    public function withMinute(string $fieldExpression): self
    {
        return $this->newInstance([ExpressionField::MINUTE->value => $fieldExpression] + $this->fields);
    }

    /**
     * @param array<string> $fields
     */
    private function newInstance(array $fields): self
    {
        $newExpression = implode(' ', [
            $fields[ExpressionField::MINUTE->value],
            $fields[ExpressionField::HOUR->value],
            $fields[ExpressionField::MONTHDAY->value],
            $fields[ExpressionField::MONTH->value],
            $fields[ExpressionField::WEEKDAY->value],
        ]);

        if ($newExpression === $this->toString()) {
            return $this;
        }

        return new self($newExpression);
    }

    public function withHour(string $fieldExpression): self
    {
        return $this->newInstance([ExpressionField::HOUR->value => $fieldExpression] + $this->fields);
    }

    public function withDayOfMonth(string $fieldExpression): self
    {
        return $this->newInstance([ExpressionField::MONTHDAY->value => $fieldExpression] + $this->fields);
    }

    public function withMonth(string $fieldExpression): self
    {
        return $this->newInstance([ExpressionField::MONTH->value => $fieldExpression] + $this->fields);
    }

    public function withDayOfWeek(string $fieldExpression): self
    {
        return $this->newInstance([ExpressionField::WEEKDAY->value => $fieldExpression] + $this->fields);
    }
}
