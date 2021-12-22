PHP Cron Expression Parser
==========================

**NOTE** This is a fork of [https://github.com/dragonmantank/cron-expression](https://github.com/dragonmantank/cron-expression).  

The main difference is to be found in the exposed public API.

The main class `CronExpression` is made an Immutable Value Object and the public API is made
easier to reason with.

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
$cron = new CronExpression('@daily');
$cron->match();
echo $cron->nextRun()->format('Y-m-d H:i:s');
echo $cron->previousRun()->format('Y-m-d H:i:s');

// Works with complex expressions
$cron = new CronExpression('3-59/15 2,6-12 */15 1 2-5');
echo $cron->nextRun()->format('Y-m-d H:i:s');

// Calculate a run date two iterations into the future
$cron = new CronExpression('@daily');
echo $cron->nextRun(null, 2)->format('Y-m-d H:i:s');

// Calculate a run date relative to a specific time
$cron = new CronExpression('@monthly');
echo $cron->nextRun('2010-01-12 00:00:00')->format('Y-m-d H:i:s');

// Works with complex expressions and timezone
$cron = new CronExpression('45 9 * * *', 'Africa/Kinshasa');
$date = new DateTime('2014-05-18 08:45', new DateTimeZone('Europe/London'));
echo $cron->match($date); // return true
```

## CronExpression Public API

```php
<?php

namespace Bakame\Cron;

final class CronExpression implements ConfigurableExpression, JsonSerializable, Stringable
{
    public function __construct(string $expression, DateTimeZone|string|null $timezone = null, int $maxIterationCount = 1000);
    
    /* Named constructors */
    public static function yearly(DateTimeZone|string|null $timezone = null, int $maxIterationCount = 1000): self;
    public static function monthly(DateTimeZone|string|null $timezone = null, int $maxIterationCount = 1000): self;
    public static function weekly(DateTimeZone|string|null $timezone = null, int $maxIterationCount = 1000): self;
    public static function daily(DateTimeZone|string|null $timezone = null, int $maxIterationCount = 1000): self;
    public static function hourly(DateTimeZone|string|null $timezone = null, int $maxIterationCount = 1000): self;
    public static function isValid(string $expression): bool;
    
    /** CRON Expression API */
    public function nextRun(
        DateTimeInterface|string|null $from = 'now',
        int $nth = 0,
        int $options = self::DISALLOW_CURRENT_DATE
    ): DateTimeImmutable;
    public function previousRun(
        DateTimeInterface|string|null $from = 'now',
        int $nth = 0,
        int $options = self::DISALLOW_CURRENT_DATE
    ): DateTimeImmutable;
    public function nextOccurrences(
        int $total,
        DateTimeInterface|string|null $from = 'now',
        int $options = self::DISALLOW_CURRENT_DATE
    ): Generator;
    public function previousOccurrences(
        int $total,
        DateTimeInterface|string|null $from = 'now',
        int $options = self::DISALLOW_CURRENT_DATE
    ): Generator;
   public function match(DateTimeInterface|string $datetime = 'now'): bool;

    /** CRON Expression getters */
    public function fields(): array;
    public function minute(): string;
    public function hour(): string;
    public function dayOfMonth(): string;
    public function month(): string;
    public function dayOfWeek(): string;
    public function toString(): string;
    public function __toString(): string;
    public function jsonSerialize(): string;
    
    /** CRON Expression configuration methods */
    public function maxIterationCount(): int;
    public function timezone(): DateTimeZone;
    public function withMinute(string $field): self;
    public function withHour(string $field): self;
    public function withDayOfMonth(string $field): self;
    public function withMonth(string $field): self;
    public function withDayOfWeek(string $field): self;
    public function withMaxIterationCount(int $maxIterationCount): self;
    public function withTimezone(DateTimeZone|string $timezone): self;
 }
```
