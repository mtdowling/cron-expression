Cron Parser
===========

The Cron\Parser class can parse a CRON expression, determine if it is due to run, and calculate the next run date of the expression.  The parser can handle simple increment of ranges (e.g. */12), intervals (e.g. 0-9), and lists (e.g. 1,2,3).

Requirements
------------

#. PHP 5.3+
#. PHPUnit is required to run the unit tests

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

Here are the features lacking in the current implementation:

#. Implement complex increment ranges (e.g. 3-59/15)
#. Implement L for last day of the week and last day of the month
#. Implement W to find the closest day of the week for a day of the month (e.g. 1W)
#. Implement the pound sign for the day of the week field to handle things like "the second friday of a given month"
