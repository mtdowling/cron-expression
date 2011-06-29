<?php

namespace Cron;

use DateInterval;
use DateTime;
use InvalidArgumentException;

/**
 * CRON expression parser that can determine whether or not a CRON expression is
 * due to run and the next run date of a cron schedule.  The determinations made
 * by this class are accurate if checked run once per minute.
 *
 * The parser can handle ranges (10-12), intervals (*\/10), comma separated
 * values (e.g. 12,15), special predefined values (e.g. @yearly),
 *
 * Schedule parts must map to:
 * minute [0-59], hour [0-23], day of month, month [1-12], day of week [1-7]
 *
 * @author Michael Dowling <mtdowling@gmail.com>
 * @link http://en.wikipedia.org/wiki/Cron
 */
class CronExpression
{
    const MINUTE = 0;
    const HOUR = 1;
    const DAY = 2;
    const MONTH = 3;
    const WEEKDAY = 4;

    /**
     * @var array CRON expression parts
     */
    private $cronParts;

    /**
     * Factory method to create a new CronExpression.
     *
     * @param string $expression The CRON expression to create.  There are
     *      several special predefined values which can be used to substitute the
     *      CRON expression:
     *
     *      @yearly, @annually) - Run once a year, midnight, Jan. 1 - 0 0 1 1 *
     *      @monthly - Run once a month, midnight, first of month - 0 0 1 * *
     *      @weekly - Run once a week, midnight on Sun - 0 0 * * 0
     *      @daily - Run once a day, midnight - 0 0 * * *
     *      @hourly - Run once an hour, first minute - 0 * * * *
     *
     * @return CronExpression
     */
    public static function factory($expression)
    {
        $mappings = array(
            '@yearly' => '0 0 1 1 *',
            '@annually' => '0 0 1 1 *',
            '@monthly' => '0 0 1 * *',
            '@weekly' => '0 0 * * 0',
            '@daily' => '0 0 * * *',
            '@hourly' => '0 * * * *'
        );

        if (isset($mappings[$expression])) {
            $expression = $mappings[$expression];
        }

        return new self($expression);
    }

    /**
     * Parse a CRON expression
     *
     * @param string $schedule CRON expression schedule string (e.g. '8 * * * *')
     *
     * @throws InvalidArgumentException if not a valid CRON expression
     */
    public function __construct($schedule)
    {
        $this->cronParts = explode(' ', $schedule);

        if (count($this->cronParts) != 5) {
            throw new InvalidArgumentException(
                $schedule . ' is not a valid CRON expression'
            );
        }
    }

    /**
     * Get the date in which the CRON will run next
     *
     * @param string $currentTime (optional) Optionally set the current date
     *      time for testing purposes or disregarding the current second
     *
     * @return DateTime
     */
    public function getNextRunDate($currentTime = 'now')
    {
        $currentDate = $currentTime instanceof DateTime
            ? $currentTime
            : new DateTime($currentTime ?: 'now');

        $nextRun = clone $currentDate;
        $nextRun->setTime($nextRun->format('H'), $nextRun->format('i'), 0);

        // Set a hard limit to bail on an impossible date
        for ($i = 0; $i < 10000; $i++) {

            // Adjust the month until it matches.  Reset day to 1 and reset time.
            if (!$this->unitSatisfiesCron($nextRun, 'm', $this->getExpression(self::MONTH))) {
                $nextRun->add(new DateInterval('P1M'));
                $nextRun->setDate($nextRun->format('Y'), $nextRun->format('m'), 1);
                $nextRun->setTime(0, 0, 0);
                continue;
            }

            // Adjust the day of the month by incrementing the day until it matches. Reset time.
            if (!$this->unitSatisfiesCron($nextRun, 'd', $this->getExpression(self::DAY))) {
                $nextRun->add(new DateInterval('P1D'));
                $nextRun->setTime(0, 0, 0);
                continue;
            }

            // Adjust the day of week by incrementing the day until it matches.  Resest time.
            // According cron implementation, 0 si we use 'w' format
            if (!$this->unitSatisfiesCron($nextRun, 'w', $this->getExpression(self::WEEKDAY))) {
                $nextRun->add(new DateInterval('P1D'));
                $nextRun->setTime(0, 0, 0);
                continue;
            }

            // Adjust the hour until it matches the set hour.  Set seconds and minutes to 0
            if (!$this->unitSatisfiesCron($nextRun, 'H', $this->getExpression(self::HOUR))) {
                $nextRun->add(new DateInterval('PT1H'));
                $nextRun->setTime($nextRun->format('H'), 0, 0);
                continue;
            }

            // Adjust the minutes until it matches a set minute
            if (!$this->unitSatisfiesCron($nextRun, 'i', $this->getExpression(self::MINUTE))) {
                $nextRun->add(new DateInterval('PT1M'));
                continue;
            }

            // If the suggested next run time is not after the current time, then keep iterating
            if ($currentTime != 'now' && $currentDate > $nextRun) {
                $nextRun->add(new DateInterval('PT1M'));
                continue;
            }

            break;
        }

        return $nextRun;
    }

    /**
     * Get all or part of the CRON expression
     *
     * @param string $part (optional) Specify the part to retrieve or NULL to
     *      get the full cron schedule string.
     *
     * @return string|null Returns the CRON expression, a part of the
     *      CRON expression, or NULL if the part was specified but not found
     */
    public function getExpression($part = null)
    {
        if (null === $part) {
            return implode(' ', $this->cronParts);
        } else if (array_key_exists($part, $this->cronParts)) {
            return $this->cronParts[$part];
        }

        return null;
    }

    /**
     * Deterime if the cron is due to run based on the current time.  Unless
     * a string is passed, this method assumes that the current number of
     * seconds are irrelevant, and that this method will be called once per
     * minute.
     *
     * @param string|DateTime $currentTime (optional) Set the current time
     *      If left NULL, the current time is used, less seconds
     *      If a DateTime object is passed, the DateTime is used less seconds
     *      If a string is used, the exact strotime of the string is used
     *
     * @return bool Returns TRUE if the cron is due to run or FALSE if not
     */
    public function isDue($currentTime = null)
    {
        if (null === $currentTime || 'now' === $currentTime) {
            $currentDate = date('Y-m-d H:i');
            $currentTime = strtotime($currentDate);
        } else if ($currentTime instanceof DateTime) {
            $currentDate = $currentTime->format('Y-m-d H:i');
            $currentTime = strtotime($currentDate);
        } else {
            $currentDate = $currentTime;
            $currentTime = strtotime($currentTime);
        }

        return $this->getNextRunDate($currentDate)->getTimestamp() == $currentTime;
    }

    /**
     * Check if a date/time unit value satisfies a crontab unit
     *
     * @param DateTime $nextRun Current next run date
     * @param string $unit Date/time unit type (e.g. Y, m, d, H, i)
     * @param string $schedule Cron schedule variable
     *
     * @return bool Returns TRUE if the unit satisfies the constraint
     */
    protected function unitSatisfiesCron(DateTime $nextRun, $unit, $schedule)
    {
        if ($schedule === '*') {
            return true;
        }

        $unitValue = (int) $nextRun->format($unit);

        // According cron implementation, 0|7 = sunday, so we replace it
        if ($unit == 'w' && strpos($schedule, '7') !== false) {
            $schedule = str_replace('7','0', $schedule);
        }

        // Check increments of ranges
        if (strpos($schedule, '*/') !== false) {
            list($delimiter, $interval) = explode('*/', $schedule);
            return $unitValue % (int) $interval == 0;
        }

        // Check intervals
        if (strpos($schedule, '-')) {
            list($first, $last) = explode('-', $schedule);
            if ($unit == 'w' && $last == 0) {
                return $this->unitSatisfiesCron($nextRun, $unit, sprintf('0,%u-6',$first));
            }
            return $unitValue >= $first && $unitValue <= $last;
        }

        // Check lists of values
        if (strpos($schedule, ',')) {
            foreach (array_map('trim', explode(',', $schedule)) as $test) {
                if ($this->unitSatisfiesCron($nextRun, $unit, $test)) {
                    return true;
                }
            }

            return false;
        }

        return $unitValue == (int) $schedule;
    }
}