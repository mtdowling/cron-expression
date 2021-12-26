<?php

declare(strict_types=1);

namespace Bakame\Cron;

use JsonSerializable;
use Stringable;

final class CronExpression implements Expression, JsonSerializable, Stringable
{
    /** @var array<int, string> CRON expression fields */
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
        return $this->fields[ExpressionParser::MINUTE];
    }

    public function hour(): string
    {
        return $this->fields[ExpressionParser::HOUR];
    }

    public function dayOfMonth(): string
    {
        return $this->fields[ExpressionParser::MONTHDAY];
    }

    public function month(): string
    {
        return $this->fields[ExpressionParser::MONTH];
    }

    public function dayOfWeek(): string
    {
        return $this->fields[ExpressionParser::WEEKDAY];
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

    public function withMinute(string $field): self
    {
        return $this->newInstance([ExpressionParser::MINUTE => $field] + $this->fields);
    }

    /**
     * @param array<int, string|int> $parts
     */
    private function newInstance(array $parts): self
    {
        ksort($parts);
        if ($parts === $this->fields) {
            return $this;
        }

        $clone = clone $this;
        $clone->fields = ExpressionParser::parse(implode(' ', $parts));

        return $clone;
    }

    public function withHour(string $field): self
    {
        return $this->newInstance([ExpressionParser::HOUR => $field] + $this->fields);
    }

    public function withDayOfMonth(string $field): self
    {
        return $this->newInstance([ExpressionParser::MONTHDAY => $field] + $this->fields);
    }

    public function withMonth(string $field): self
    {
        return $this->newInstance([ExpressionParser::MONTH => $field] + $this->fields);
    }

    public function withDayOfWeek(string $field): self
    {
        return $this->newInstance([ExpressionParser::WEEKDAY => $field] + $this->fields);
    }
}
