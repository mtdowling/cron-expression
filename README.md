Cron Expression
===============

PHP cron expression parser that can parse a CRON expression, determine if it is
due to run, and calculate the next run date of the expression.  The parser can
handle increments of ranges (e.g. */12, 2-59/3), intervals (e.g. 0-9), lists
(e.g. 1,2,3), W to find the nearest weekday for a given day of the month, and
L to find the last day of the month.

Requirements
------------

- PHP 5.3+
- PHPUnit is required to run the unit tests

Usage
-----

    <?php

    // Works with predefined scheduling definitions
    $cron = Cron\CronExpression::factory('@daily');
    $cron->isDue();
    echo $cron->getNextRunDate();

    // Works with complex expressions
    $cron = new Cron\CronExpression('3-59/15 2,6-12 */15 1 2-5');
    echo $cron->getNextRunDate();

CRON Expressions
----------------

A CRON expression is a string representing the schedule for a particular command to execute.  The parts of a CRON schedule are as follows:

    *    *    *    *    *  command to be executed
    -    -    -    -    -
    |    |    |    |    |
    |    |    |    |    |
    |    |    |    |    +----- day of week (0 - 7) (Sunday=0 or 7)
    |    |    |    +---------- month (1 - 12)
    |    |    +--------------- day of month (1 - 31)
    |    +-------------------- hour (0 - 23)
    +------------------------- min (0 - 59)

TODO
----

1. Implement hash for the day of the week field to handle things like "the second friday of a given month"
2. Add code coverage for DayOfMonth and DayOfYear
3. Implement phar build process and autoloader