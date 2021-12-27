PHP Cron Expression Parser
==========================

**NOTE** This is a fork of [https://github.com/mtdowling/cron-expression](https://github.com/mtdowling/cron-expression).  

The main difference is to be found in the exposed public API.

- The main class `CronExpression` is made an Immutable Value Object and the public API is updated to reflect it.
- An independent class `Scheduler` is added to improve with working with the business logic.

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
use Bakame\Cron\Scheduler;

require_once '/vendor/autoload.php';

// Works with predefined scheduling definitions
$cron = CronExpression::daily();
echo $cron; // returns '0 0 * * *'
$scheduler = new Scheduler($cron);
$scheduler->isDue();
echo $scheduler->run()->format('Y-m-d H:i:s');
echo $scheduler->run(-1)->format('Y-m-d H:i:s');

// Works with complex expressions
$scheduler = new Scheduler(new CronExpression('3-59/15 2,6-12 */15 1 2-5'));
echo $scheduler->run()->format('Y-m-d H:i:s');

// Calculate a run date two iterations into the future
$scheduler = new Scheduler('@daily');
echo $scheduler->run(2)->format('Y-m-d H:i:s');

// Calculate a run date relative to a specific time
$scheduler = new Scheduler(new CronExpression::monthly());
echo $scheduler->run(0, '2010-01-12 00:00:00')->format('Y-m-d H:i:s');
// or
$scheduler = new Scheduler(new CronExpression::monthly());
echo $scheduler->run(relativeTo: '2010-01-12 00:00:00')->format('Y-m-d H:i:s');

// Works with complex expressions and timezone
$scheduler = new Scheduler('45 9 * * *', 'Africa/Kinshasa');
$date = new DateTime('2014-05-18 08:45', new DateTimeZone('Europe/London'));
echo $scheduler->isDue($date); // return true
```

## Cron scheduler Public API

```php
<?php

namespace Bakame\Cron;

final class Scheduler
{
    public const EXCLUDE_START_DATE = 0;
    public const INCLUDE_START_DATE = 1;
    
    /* CRON Expression Scheduler Constructors */
    public function __construct(Expression|string $expression, DateTimeZone|string|null $timezone = null, int $maxIterationCount = 1000, int $options = self::EXCLUDE_START_DATE);
    public static function fromUTC(Expression|string $expression): self;
    public static function fromSystemTimeZone(Expression|string $expression): self;

    /* CRON Expression Scheduler API */
    public function run(int $nth = 0, DateTimeInterface|string $relativeTo = 'now'): DateTimeImmutable;
    public function yieldRunsForward(int $total, DateTimeInterface|string $relativeTo = 'now'): Generator;
    public function yieldRunsBackward(int $total, DateTimeInterface|string $relativeTo = 'now'): Generator;
    public function isDue(DateTimeInterface|string $dateTime = 'now'): bool;
    
     /* CRON Expression Scheduler Configuration API */
    public function expression(): Expression;
    public function withExpression(Expression $expression): self;
    public function timeZone(): DateTimeZone;
    public function withTimeZone(DateTimeZone|string $timeZone): self;
    public function maxIterationCount(): int;
    public function withMaxIterationCount(int $maxIterationCount): self;
    public function isStartDateExcluded(): bool;
    public function excludeStartDate(): self;
    public function includeStartDate(): self;
 }
```

## CronExpression Public API

```php
<?php

namespace Bakame\Cron;

final class CronExpression implements Expression, \JsonSerializable, \Stringable
{
    /* Constructors */
    public function __construct(string $expression);
    public static function yearly(): self;
    public static function monthly(): self;
    public static function weekly(): self;
    public static function daily(): self;
    public static function hourly(): self;

    /* CRON Expression getters */
    public function fields(): array;
    public function minute(): string;
    public function hour(): string;
    public function dayOfMonth(): string;
    public function month(): string;
    public function dayOfWeek(): string;
    
    /* CRON Expression configuration methods */
    public function withMinute(string $fieldValue): self;
    public function withHour(string $fieldValue): self;
    public function withDayOfMonth(string $fieldValue): self;
    public function withMonth(string $fieldValue): self;
    public function withDayOfWeek(string $fieldValue): self;
    
    /* CRON expression formatting */
    public function toString(): string;
    public function __toString(): string;
    public function jsonSerialize(): string;
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

## FieldValidator Public API

```php
<?php

final class FieldValidator
{
    public function isSatisfiedBy(DateTimeInterface $date, string $expression): bool;
    public function increment(DateTime|DateTimeImmutable $date, bool $invert = false, string $parts = null): DateTime|DateTimeImmutable;
    public function validate(string $expression): bool;
}
```
