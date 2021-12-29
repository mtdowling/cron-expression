<?php

namespace Bakame\Cron;

use ReflectionClass;
use UnexpectedValueException;

/**
 * @method static self MINUTE()
 * @method static self HOUR()
 * @method static self MONTHDAY()
 * @method static self MONTH()
 * @method static self WEEKDAY()
 */
final class ExpressionField
{
    public const MINUTE = 'minute';
    public const HOUR = 'hour';
    public const MONTHDAY = 'dayOfMonth';
    public const MONTH = 'month';
    public const WEEKDAY = 'dayOfWeek';

    private string $value;

    private function __construct(string $value)
    {
        if (!in_array($value, self::constants(), true)) {
            throw new UnexpectedValueException('The Expression Field name `'.$value.'` is unknown or not supported.');
        }

        $this->value = $value;
    }

    /**
     * @throws UnexpectedValueException If the value is unknown or not supprted
     */
    public static function __callStatic(string $name, array $arguments = []): self
    {
        if (defined("self::$name")) {
            /** @var string */
            $enumCase = constant("self::$name");
            return new self($enumCase);
        }

        throw new UnexpectedValueException('The Expression Field name `'.$name.'` is unknown or not supported.');
    }

    /**
     * @return array<string, string>
     */
    private static function constants(): array
    {
        static $list;
        if (null === $list) {
            $list = (new ReflectionClass(self::class))->getConstants();
        }

        return $list;
    }

    public static function from(string $value): self
    {
        return new self($value);
    }

    public static function tryFrom(string $value): self|null
    {
        try {
            return self::from($value);
        } catch (\UnexpectedValueException) {
            return null;
        }
    }

    /**
     * @return array<ExpressionField>
     */
    public static function cases(): array
    {
        static $res = [];
        if ([] === $res) {
            foreach (self::constants() as $value) {
                $res[] = new self($value);
            }
        }

        return $res;
    }

    public function name(): string
    {
        /** @var string $name */
        $name = array_search($this->value, self::constants(), true);

        return $name;
    }

    public function value(): string
    {
        return $this->value;
    }

    /**
     * Get an instance of a field validator object for a cron expression position.
     */
    public function validator(): CronFieldValidator
    {
        return match (true) {
            self::MINUTE === $this->value => new MinuteValidator(),
            self::HOUR === $this->value => new HourValidator(),
            self::MONTHDAY === $this->value => new DayOfMonthValidator(),
            self::MONTH === $this->value => new MonthValidator(),
            default => new DayOfWeekValidator(),
        };
    }

    public static function fromOffset(int $position): self
    {
        return match (true) {
            0 === $position => self::MINUTE(),
            1 === $position => self::HOUR(),
            2 === $position => self::MONTHDAY(),
            3 === $position => self::MONTH(),
            4 === $position => self::WEEKDAY(),
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
        static $order = [];
        if ([] === $order) {
            $order = [
                self::MONTH(),
                self::MONTHDAY(),
                self::WEEKDAY(),
                self::HOUR(),
                self::MINUTE(),
            ];
        }

        return $order;
    }
}
