PHP Cron Expression Parser
==========================

[![Latest Stable Version](https://poser.pugx.org/mtdowling/cron-expression/v/stable.png)](https://packagist.org/packages/mtdowling/cron-expression) [![Total Downloads](https://poser.pugx.org/mtdowling/cron-expression/downloads.png)](https://packagist.org/packages/mtdowling/cron-expression) [![Build Status](https://secure.travis-ci.org/mtdowling/cron-expression.png)](http://travis-ci.org/mtdowling/cron-expression)

**NOTE** This is a fork of [https://github.com/dragonmantank/cron-expression](https://github.com/dragonmantank/cron-expression).  

The main difference is to be found in the exposed public API.

The main class `CronExpression` is made an Immutable Value Object and the public API is made
easier to reason with.

To know more about cron expression your can look at the [Unix documentation](https://www.unix.com/man-page/linux/5/crontab/)

## System Requirements

You need **PHP >= 8.0** and the `mbstring` extension to use `Csv` but the latest stable version of PHP is recommended.

## Installing

Add the dependency to your project:

```bash
composer require bakame-php/cron-expression
```

Usage
=====

```php
<?php

use Cron\CronExpression;

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
