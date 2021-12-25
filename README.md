PHP Cron Expression Parser
==========================

**NOTE** This is a fork of [https://github.com/mtdowling/cron-expression](https://github.com/mtdowling/cron-expression).  

The main difference is to be found in the exposed public API.

The main class `CronExpression` is made an Immutable Value Object and the public API is updated to reflect it.

To know more about cron expression your can look at the [Unix documentation](https://www.unix.com/man-page/linux/5/crontab/)

## System Requirements

You need **PHP >= 8.0** but the latest stable version of PHP is recommended.

## Installing

Add the dependency to your project:

```bash
composer require bakame-php/cron-expression
```

Usage
=====

```php
<?php

use Bakame\Cron\CronExpression;

require_once '/vendor/autoload.php';

// Works with predefined scheduling definitions
$cron = CronExpression::daily();
$cron->match();
echo $cron; // returns '0 0 * * *'
echo $cron->run()->format('Y-m-d H:i:s');
echo $cron->run(-1)->format('Y-m-d H:i:s');

// Works with complex expressions
$cron = new CronExpression('3-59/15 2,6-12 */15 1 2-5');
echo $cron->run()->format('Y-m-d H:i:s');

// Calculate a run date two iterations into the future
$cron = new CronExpression('@daily');
echo $cron->run(2)->format('Y-m-d H:i:s');

// Calculate a run date relative to a specific time
$cron = new CronExpression::monthly();
echo $cron->run(0, '2010-01-12 00:00:00')->format('Y-m-d H:i:s');
// or
echo $cron->run(from: '2010-01-12 00:00:00')->format('Y-m-d H:i:s');

// Works with complex expressions and timezone
$cron = new CronExpression('45 9 * * *', 'Africa/Kinshasa');
$date = new DateTime('2014-05-18 08:45', new DateTimeZone('Europe/London'));
echo $cron->match($date); // return true
```

## CronExpression Public API

```php
<?php

namespace Bakame\Cron;

final class CronExpression implements EditableExpression, \JsonSerializable, \Stringable
{
    /* Constructors */
    public function __construct(string $expression, DateTimeZone|string|null $timezone = null, int $maxIterationCount = 1000);
    public static function yearly(DateTimeZone|string|null $timezone = null, int $maxIterationCount = 1000): self;
    public static function monthly(DateTimeZone|string|null $timezone = null, int $maxIterationCount = 1000): self;
    public static function weekly(DateTimeZone|string|null $timezone = null, int $maxIterationCount = 1000): self;
    public static function daily(DateTimeZone|string|null $timezone = null, int $maxIterationCount = 1000): self;
    public static function hourly(DateTimeZone|string|null $timezone = null, int $maxIterationCount = 1000): self;

    /* CRON Expression API */
    public function run(int $nth = 0, DateTimeInterface|string $from = 'now', int $options = self::DISALLOW_CURRENT_DATE): DateTimeImmutable;
    public function yieldNextRuns(int $total, DateTimeInterface|string $from = 'now',  int $options = self::DISALLOW_CURRENT_DATE): Generator;
    public function yieldPreviousRuns(int $total, DateTimeInterface|string $from = 'now', int $options = self::DISALLOW_CURRENT_DATE): Generator;
    public function match(DateTimeInterface|string $datetime = 'now',): bool;

    /* CRON Expression getters */
    public function timezone(): DateTimeZone;
    public function fields(): array;
    public function minute(): string;
    public function hour(): string;
    public function dayOfMonth(): string;
    public function month(): string;
    public function dayOfWeek(): string;
    public function toString(): string;
    public function __toString(): string;
    public function jsonSerialize(): string;
    
    /* CRON Expression configuration methods */
    public function withMinute(string $field): self;
    public function withHour(string $field): self;
    public function withDayOfMonth(string $field): self;
    public function withMonth(string $field): self;
    public function withDayOfWeek(string $field): self;
    public function withTimezone(DateTimeZone|string $timezone): self;
    public function maxIterationCount(): int;
    public function withMaxIterationCount(int $maxIterationCount): self;
 }
```

## ExpressionParser Public API

```php
<?php

final class ExpressionParser
{
    public const MINUTE = 0;
    public const HOUR = 1;
    public const MONTHDAY = 2;
    public const MONTH = 3;
    public const WEEKDAY = 4;

    public static function fieldValidator(int $fieldOffset): FieldValidator;
    public static function parse(string $expression): array;
    public static function isValid(string $expression): bool;
}
```

## ExpressionParser Public API

```php
<?php

final class FieldValidator
{
    public function isSatisfiedBy(DateTimeInterface $date, string $expression): bool;
    public function increment(DateTimeInterface $date, bool $invert = false, string $parts = null): DateTimeInterface;
    public function validate(string $expression): bool;
}
```
