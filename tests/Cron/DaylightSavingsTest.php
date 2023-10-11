<?php
declare(strict_types=1);

namespace Cron\Tests;

use Cron\CronExpression;
use PHPUnit\Framework\TestCase;

class DaylightSavingsTest extends TestCase
{
    public function testIssue111(): void
    {
        $expression = "0 1 * * 0";
        $cron = new CronExpression($expression);
        $tz = new \DateTimeZone("Europe/London");

        $dtExpected = $this->createDateTimeExactly("2021-03-28 02:00+01:00", $tz);

        $dtCurrent = \DateTimeImmutable::createFromFormat("!Y-m-d H:i:s", "2021-03-28 14:55:03", $tz);
        $dtActual = $cron->getPreviousRunDate($dtCurrent, 0, false, $tz->getName());
        $this->assertEquals($dtExpected, $dtActual);

        $dtCurrent = $this->createDateTimeExactly("2021-04-21 00:00+01:00", $tz);
        $dtActual = $cron->getPreviousRunDate($dtCurrent, 3, true, $tz->getName());
        $this->assertEquals($dtExpected, $dtActual);
    }

    public function testIssue112(): void
    {
        $expression = "15 2 * * 0";
        $cron = new CronExpression($expression);
        $tz = new \DateTimeZone("America/Winnipeg");

        $dtCurrent = $this->createDateTimeExactly("2021-03-08 08:15-06:00", $tz);
        $dtExpected = $this->createDateTimeExactly("2021-03-14 03:15-05:00", $tz);
        $this->assertEquals($dtExpected, $cron->getNextRunDate($dtCurrent));
    }

    /**
     * Create a DateTimeImmutable that represents the given exact moment in time.
     * This is a bit finicky because DateTime likes to override the timezone with the offset even when it's valid
     *  and in some cases necessary during DST changes.
     * Assertions verify no unexpected behavior changes in PHP.
     */
    protected function createDateTimeExactly($dtString, \DateTimeZone $timezone)
    {
        $dt = \DateTimeImmutable::createFromFormat("!Y-m-d H:iO", $dtString, $timezone);
        $dt = $dt->setTimezone($timezone);
        $this->assertEquals($dtString, $dt->format("Y-m-d H:iP"));
        $this->assertEquals($timezone->getName(), $dt->format("e"));
        return $dt;
    }

    public function testOffsetIncrementsNextRunDate(): void
    {
        $tz = new \DateTimeZone("Europe/London");
        $cron = new CronExpression("0 1 * * 0");

        $dtCurrent = $this->createDateTimeExactly("2021-03-21 00:00+00:00", $tz);
        $dtExpected = $this->createDateTimeExactly("2021-03-21 01:00+00:00", $tz);
        $this->assertEquals($dtExpected, $cron->getNextRunDate($dtCurrent, 0, false, $tz->getName()));

        $dtCurrent = $this->createDateTimeExactly("2021-03-21 02:00+00:00", $tz);
        $dtExpected = $this->createDateTimeExactly("2021-03-28 02:00+01:00", $tz);
        $this->assertEquals($dtExpected, $cron->getNextRunDate($dtCurrent, 0, false, $tz->getName()));

        $dtCurrent = $this->createDateTimeExactly("2021-03-28 00:00+00:00", $tz);
        $dtExpected = $this->createDateTimeExactly("2021-03-28 02:00+01:00", $tz);
        $this->assertEquals($dtExpected, $cron->getNextRunDate($dtCurrent, 0, false, $tz->getName()));

        $dtCurrent = $this->createDateTimeExactly("2021-03-28 02:00+01:00", $tz);
        $dtExpected = $this->createDateTimeExactly("2021-03-28 02:00+01:00", $tz);
        $this->assertEquals($dtExpected, $cron->getNextRunDate($dtCurrent, 0, true, $tz->getName()));

        $dtCurrent = $this->createDateTimeExactly("2021-03-28 02:00+01:00", $tz);
        $dtExpected = $this->createDateTimeExactly("2021-04-04 01:00+01:00", $tz);
        $this->assertEquals($dtExpected, $cron->getNextRunDate($dtCurrent, 0, false, $tz->getName()));

        $dtCurrent = $this->createDateTimeExactly("2021-03-28 02:05+01:00", $tz);
        $dtExpected = $this->createDateTimeExactly("2021-04-04 01:00+01:00", $tz);
        $this->assertEquals($dtExpected, $cron->getNextRunDate($dtCurrent, 0, false, $tz->getName()));
    }

    public function testOffsetIncrementsPreviousRunDate(): void
    {
        $tz = new \DateTimeZone("Europe/London");
        $cron = new CronExpression("0 1 * * 0");

        $dtCurrent = $this->createDateTimeExactly("2021-04-04 02:00+01:00", $tz);
        $dtExpected = $this->createDateTimeExactly("2021-04-04 01:00+01:00", $tz);
        $this->assertEquals($dtExpected, $cron->getPreviousRunDate($dtCurrent, 0, false, $tz->getName()));

        $dtCurrent = $this->createDateTimeExactly("2021-04-04 00:00+01:00", $tz);
        $dtExpected = $this->createDateTimeExactly("2021-03-28 02:00+01:00", $tz);
        $this->assertEquals($dtExpected, $cron->getPreviousRunDate($dtCurrent, 0, false, $tz->getName()));

        $dtCurrent = $this->createDateTimeExactly("2021-03-28 03:00+01:00", $tz);
        $dtExpected = $this->createDateTimeExactly("2021-03-28 02:00+01:00", $tz);
        $this->assertEquals($dtExpected, $cron->getPreviousRunDate($dtCurrent, 0, false, $tz->getName()));

        $dtCurrent = $this->createDateTimeExactly("2021-03-28 02:00+01:00", $tz);
        $dtExpected = $this->createDateTimeExactly("2021-03-21 01:00+00:00", $tz);
        $this->assertEquals($dtExpected, $cron->getPreviousRunDate($dtCurrent, 0, false, $tz->getName()));

        $dtCurrent = $this->createDateTimeExactly("2021-03-28 03:00+01:00", $tz);
        $dtExpected = $this->createDateTimeExactly("2021-03-28 02:00+01:00", $tz);
        $this->assertEquals($dtExpected, $cron->getPreviousRunDate($dtCurrent, 0, true, $tz->getName()));

        $dtCurrent = $this->createDateTimeExactly("2021-03-28 00:00+00:00", $tz);
        $dtExpected = $this->createDateTimeExactly("2021-03-21 01:00+00:00", $tz);
        $this->assertEquals($dtExpected, $cron->getPreviousRunDate($dtCurrent, 0, false, $tz->getName()));
    }

    /**
     * Skip due to PHP 8.1 date instabilities.
     * For further references, see https://github.com/dragonmantank/cron-expression/issues/133
     * @requires PHP <= 8.0.22
     */
    public function testOffsetDecrementsNextRunDateAllowCurrent(): void
    {
        $tz = new \DateTimeZone("Europe/London");
        $cron = new CronExpression("0 1 * * 0");

        $dtCurrent = $this->createDateTimeExactly("2020-10-18 00:00+01:00", $tz);
        $dtExpected = $this->createDateTimeExactly("2020-10-18 01:00+01:00", $tz);
        $this->assertEquals($dtExpected, $cron->getNextRunDate($dtCurrent, 0, true, $tz->getName()));

        $dtCurrent = $this->createDateTimeExactly("2020-10-18 01:05+01:00", $tz);
        $dtExpected = $this->createDateTimeExactly("2020-10-25 01:00+00:00", $tz);
        $this->assertEquals($dtExpected, $cron->getNextRunDate($dtCurrent, 0, true, $tz->getName()));

        $dtCurrent = $this->createDateTimeExactly("2020-10-25 00:00+01:00", $tz);
        $dtExpected = $this->createDateTimeExactly("2020-10-25 01:00+00:00", $tz);
        $this->assertEquals($dtExpected, $cron->getNextRunDate($dtCurrent, 0, true, $tz->getName()));

        $dtCurrent = $this->createDateTimeExactly("2020-10-25 01:00+01:00", $tz);
        $dtExpected = $this->createDateTimeExactly("2020-10-25 01:00+01:00", $tz);
        $this->assertEquals($dtExpected, $cron->getNextRunDate($dtCurrent, 0, true, $tz->getName()));

        $dtCurrent = $this->createDateTimeExactly("2020-10-25 01:00+00:00", $tz);
        $dtExpected = $this->createDateTimeExactly("2020-10-25 01:00+00:00", $tz);
        $this->assertEquals($dtExpected, $cron->getNextRunDate($dtCurrent, 0, true, $tz->getName()));

        $dtCurrent = $this->createDateTimeExactly("2020-10-25 01:05+00:00", $tz);
        $dtExpected = $this->createDateTimeExactly("2020-11-01 01:00+00:00", $tz);
        $this->assertEquals($dtExpected, $cron->getNextRunDate($dtCurrent, 0, true, $tz->getName()));
    }

    /**
     * The fact that crons will run twice using this setup is expected.
     * This can be avoided by using disallowing the current date or with additional checks outside this library
     * Skip due to PHP 8.1 date instabilities.
     * For further references, see https://github.com/dragonmantank/cron-expression/issues/133
     * @requires PHP <= 8.0.22
     */
    public function testOffsetDecrementsNextRunDateDisallowCurrent(): void
    {
        $tz = new \DateTimeZone("Europe/London");
        $cron = new CronExpression("0 1 * * 0");

        $dtCurrent = $this->createDateTimeExactly("2020-10-18 00:00+01:00", $tz);
        $dtExpected = $this->createDateTimeExactly("2020-10-18 01:00+01:00", $tz);
        $this->assertEquals($dtExpected, $cron->getNextRunDate($dtCurrent, 0, false, $tz->getName()));

        $dtCurrent = $this->createDateTimeExactly("2020-10-18 01:05+01:00", $tz);
        $dtExpected = $this->createDateTimeExactly("2020-10-25 01:00+00:00", $tz);
        $this->assertEquals($dtExpected, $cron->getNextRunDate($dtCurrent, 0, false, $tz->getName()));

        $dtCurrent = $this->createDateTimeExactly("2020-10-25 00:00+01:00", $tz);
        $dtExpected = $this->createDateTimeExactly("2020-10-25 01:00+00:00", $tz);
        $this->assertEquals($dtExpected, $cron->getNextRunDate($dtCurrent, 0, false, $tz->getName()));

        $dtCurrent = $this->createDateTimeExactly("2020-10-25 01:00+01:00", $tz);
        $dtExpected = $this->createDateTimeExactly("2020-10-25 01:00+00:00", $tz);
        $this->assertEquals($dtExpected, $cron->getNextRunDate($dtCurrent, 0, false, $tz->getName()));

        $dtCurrent = $this->createDateTimeExactly("2020-10-25 01:05+01:00", $tz);
        $dtExpected = $this->createDateTimeExactly("2020-10-25 01:00+00:00", $tz);
        $this->assertEquals($dtExpected, $cron->getNextRunDate($dtCurrent, 0, false, $tz->getName()));

        $dtCurrent = $this->createDateTimeExactly("2020-10-25 01:00+00:00", $tz);
        $dtExpected = $this->createDateTimeExactly("2020-11-01 01:00+00:00", $tz);
        $this->assertEquals($dtExpected, $cron->getNextRunDate($dtCurrent, 0, false, $tz->getName()));

        $dtCurrent = $this->createDateTimeExactly("2020-10-25 01:05+00:00", $tz);
        $dtExpected = $this->createDateTimeExactly("2020-11-01 01:00+00:00", $tz);
        $this->assertEquals($dtExpected, $cron->getNextRunDate($dtCurrent, 0, false, $tz->getName()));
    }

    public function testOffsetDecrementsPreviousRunDate(): void
    {
        $tz = new \DateTimeZone("Europe/London");
        $cron = new CronExpression("0 1 * * 0");

        $dtCurrent = $this->createDateTimeExactly("2021-04-04 02:00+01:00", $tz);
        $dtExpected = $this->createDateTimeExactly("2021-04-04 01:00+01:00", $tz);
        $this->assertEquals($dtExpected, $cron->getPreviousRunDate($dtCurrent, 0, false, $tz->getName()));

        $dtCurrent = $this->createDateTimeExactly("2021-04-04 00:00+01:00", $tz);
        $dtExpected = $this->createDateTimeExactly("2021-03-28 02:00+01:00", $tz);
        $this->assertEquals($dtExpected, $cron->getPreviousRunDate($dtCurrent, 0, false, $tz->getName()));

        $dtCurrent = $this->createDateTimeExactly("2021-03-28 03:00+01:00", $tz);
        $dtExpected = $this->createDateTimeExactly("2021-03-28 02:00+01:00", $tz);
        $this->assertEquals($dtExpected, $cron->getPreviousRunDate($dtCurrent, 0, false, $tz->getName()));

        $dtCurrent = $this->createDateTimeExactly("2021-03-28 00:00+00:00", $tz);
        $dtExpected = $this->createDateTimeExactly("2021-03-21 01:00+00:00", $tz);
        $this->assertEquals($dtExpected, $cron->getPreviousRunDate($dtCurrent, 0, false, $tz->getName()));
    }

    public function testOffsetIncrementsMultipleRunDates(): void
    {
        $expression = "0 1 * * 0";
        $cron = new CronExpression($expression);
        $tz = new \DateTimeZone("Europe/London");

        $expected = [
            $this->createDateTimeExactly("2021-03-14 01:00+00:00", $tz),
            $this->createDateTimeExactly("2021-03-21 01:00+00:00", $tz),
            $this->createDateTimeExactly("2021-03-28 02:00+01:00", $tz),
            $this->createDateTimeExactly("2021-04-04 01:00+01:00", $tz),
            $this->createDateTimeExactly("2021-04-11 01:00+01:00", $tz),
        ];

        $dtCurrent = $this->createDateTimeExactly("2021-03-13 00:00+00:00", $tz);
        $actual = $cron->getMultipleRunDates(5, $dtCurrent, false, true, $tz->getName());
        foreach ($expected as $dtExpected) {
            $this->assertContainsEquals($dtExpected, $actual);
        }

        $dtCurrent = $this->createDateTimeExactly("2021-04-12 00:00+01:00", $tz);
        $actual = $cron->getMultipleRunDates(5, $dtCurrent, true, true, $tz->getName());
        foreach ($expected as $dtExpected) {
            $this->assertContainsEquals($dtExpected, $actual);
        }
    }

    /**
     * Skip due to PHP 8.1 date instabilities.
     * For further references, see https://github.com/dragonmantank/cron-expression/issues/133
     * @requires PHP <= 8.0.22
     */
    public function testOffsetDecrementsMultipleRunDates(): void
    {
        $expression = "0 1 * * 0";
        $cron = new CronExpression($expression);
        $tz = new \DateTimeZone("Europe/London");

        $expected = [
            $this->createDateTimeExactly("2020-10-11 01:00+01:00", $tz),
            $this->createDateTimeExactly("2020-10-18 01:00+01:00", $tz),
            $this->createDateTimeExactly("2020-10-25 01:00+00:00", $tz),
            $this->createDateTimeExactly("2020-11-01 01:00+00:00", $tz),
            $this->createDateTimeExactly("2020-11-08 01:00+00:00", $tz),
        ];

        $dtCurrent = $this->createDateTimeExactly("2020-10-10 00:00+01:00", $tz);
        $actual = $cron->getMultipleRunDates(5, $dtCurrent, false, true, $tz->getName());
        foreach ($expected as $dtExpected) {
            $this->assertContainsEquals($dtExpected, $actual);
        }

        $expected = [
            $this->createDateTimeExactly("2020-10-18 01:00+01:00", $tz),
            $this->createDateTimeExactly("2020-10-25 01:00+01:00", $tz),
            $this->createDateTimeExactly("2020-10-25 01:00+00:00", $tz),
            $this->createDateTimeExactly("2020-11-01 01:00+00:00", $tz),
            $this->createDateTimeExactly("2020-11-08 01:00+00:00", $tz),
        ];

        $dtCurrent = $this->createDateTimeExactly("2020-11-12 00:00+00:00", $tz);
        $actual = $cron->getMultipleRunDates(5, $dtCurrent, true, true, $tz->getName());

        foreach ($expected as $dtExpected) {
            $this->assertContainsEquals($dtExpected, $actual);
        }
    }

    public function testOffsetIncrementsEveryOtherHour(): void
    {
        $expression = "0 */2 * * *";
        $cron = new CronExpression($expression);
        $tz = new \DateTimeZone("Europe/London");

        $expected = [
            $this->createDateTimeExactly("2021-03-27 22:00+00:00", $tz),
            $this->createDateTimeExactly("2021-03-28 00:00+00:00", $tz),
            $this->createDateTimeExactly("2021-03-28 02:00+01:00", $tz),
            $this->createDateTimeExactly("2021-03-28 04:00+01:00", $tz),
            $this->createDateTimeExactly("2021-03-28 06:00+01:00", $tz),
        ];

        $dtCurrent = $this->createDateTimeExactly("2021-03-27 22:00+00:00", $tz);
        $actual = $cron->getMultipleRunDates(5, $dtCurrent, false, true, $tz->getName());
        foreach ($expected as $dtExpected) {
            $this->assertContainsEquals($dtExpected, $actual);
        }

        $dtCurrent = $this->createDateTimeExactly("2021-03-28 06:00+01:00", $tz);
        $actual = $cron->getMultipleRunDates(5, $dtCurrent, true, true, $tz->getName());
        foreach ($expected as $dtExpected) {
            $this->assertContainsEquals($dtExpected, $actual);
        }

        $expression = "0 1-23/2 * * *";
        $cron = new CronExpression($expression);
        $tz = new \DateTimeZone("Europe/London");

        $expected = [
            $this->createDateTimeExactly("2021-03-27 23:00+00:00", $tz),
            $this->createDateTimeExactly("2021-03-28 02:00+01:00", $tz),
            $this->createDateTimeExactly("2021-03-28 03:00+01:00", $tz),
            $this->createDateTimeExactly("2021-03-28 05:00+01:00", $tz),
            $this->createDateTimeExactly("2021-03-28 07:00+01:00", $tz),
        ];

        $dtCurrent = $this->createDateTimeExactly("2021-03-27 23:00+00:00", $tz);
        $actual = $cron->getMultipleRunDates(5, $dtCurrent, false, true, $tz->getName());
        foreach ($expected as $dtExpected) {
            $this->assertContainsEquals($dtExpected, $actual);
        }

        $dtCurrent = $this->createDateTimeExactly("2021-03-28 07:00+01:00", $tz);
        $actual = $cron->getMultipleRunDates(5, $dtCurrent, true, true, $tz->getName());
        foreach ($expected as $dtExpected) {
            $this->assertContainsEquals($dtExpected, $actual);
        }
    }

    /**
     * Skip due to PHP 8.1 date instabilities.
     * For further references, see https://github.com/dragonmantank/cron-expression/issues/133
     * @requires PHP <= 8.0.22
     */
    public function testOffsetDecrementsEveryOtherHour(): void
    {
        $expression = "0 */2 * * *";
        $cron = new CronExpression($expression);
        $tz = new \DateTimeZone("Europe/London");

        $expected = [
            $this->createDateTimeExactly("2020-10-24 22:00+01:00", $tz),
            $this->createDateTimeExactly("2020-10-25 00:00+01:00", $tz),
            $this->createDateTimeExactly("2020-10-25 01:00+00:00", $tz),
            $this->createDateTimeExactly("2020-10-25 02:00+00:00", $tz),
            $this->createDateTimeExactly("2020-10-25 04:00+00:00", $tz),
        ];

        $dtCurrent = $this->createDateTimeExactly("2020-10-24 22:00+01:00", $tz);
        $actual = $cron->getMultipleRunDates(5, $dtCurrent, false, true, $tz->getName());
        foreach ($expected as $dtExpected) {
            $this->assertContainsEquals($dtExpected, $actual);
        }

        $expected = [
            $this->createDateTimeExactly("2020-10-24 20:00+01:00", $tz),
            $this->createDateTimeExactly("2020-10-24 22:00+01:00", $tz),
            $this->createDateTimeExactly("2020-10-25 00:00+01:00", $tz),
            $this->createDateTimeExactly("2020-10-25 02:00+00:00", $tz),
            $this->createDateTimeExactly("2020-10-25 04:00+00:00", $tz),
        ];

        $dtCurrent = $this->createDateTimeExactly("2020-10-25 04:00+00:00", $tz);
        $actual = $cron->getMultipleRunDates(5, $dtCurrent, true, true, $tz->getName());
        foreach ($expected as $dtExpected) {
            $this->assertContainsEquals($dtExpected, $actual);
        }

        $expression = "0 1-23/2 * * *";
        $cron = new CronExpression($expression);
        $tz = new \DateTimeZone("Europe/London");

        $expected = [
            $this->createDateTimeExactly("2020-10-24 23:00+01:00", $tz),
            $this->createDateTimeExactly("2020-10-25 01:00+00:00", $tz),
            $this->createDateTimeExactly("2020-10-25 03:00+00:00", $tz),
            $this->createDateTimeExactly("2020-10-25 05:00+00:00", $tz),
            $this->createDateTimeExactly("2020-10-25 07:00+00:00", $tz),
        ];

        $dtCurrent = $this->createDateTimeExactly("2020-10-24 23:00+01:00", $tz);
        $actual = $cron->getMultipleRunDates(5, $dtCurrent, false, true, $tz->getName());
        foreach ($expected as $dtExpected) {
            $this->assertContainsEquals($dtExpected, $actual);
        }

        $expected = [
            $this->createDateTimeExactly("2020-10-25 01:00+01:00", $tz),
            $this->createDateTimeExactly("2020-10-25 01:00+00:00", $tz),
            $this->createDateTimeExactly("2020-10-25 03:00+00:00", $tz),
            $this->createDateTimeExactly("2020-10-25 05:00+00:00", $tz),
            $this->createDateTimeExactly("2020-10-25 07:00+00:00", $tz),
        ];

        $dtCurrent = $this->createDateTimeExactly("2020-10-25 07:00+00:00", $tz);
        $actual = $cron->getMultipleRunDates(5, $dtCurrent, true, true, $tz->getName());
        foreach ($expected as $dtExpected) {
            $this->assertContainsEquals($dtExpected, $actual);
        }
    }

    public function testOffsetIncrementsMidnight(): void
    {
        $expression = '@hourly';
        $cron = new CronExpression($expression);
        $tz = new \DateTimeZone("America/Asuncion");

        $expected = [
            $this->createDateTimeExactly("2021-03-27 22:00-03:00", $tz),
            $this->createDateTimeExactly("2021-03-27 23:00-03:00", $tz),
            $this->createDateTimeExactly("2021-03-27 23:00-04:00", $tz),
            $this->createDateTimeExactly("2021-03-28 00:00-04:00", $tz),
            $this->createDateTimeExactly("2021-03-28 01:00-04:00", $tz),
        ];

        $dtCurrent = $this->createDateTimeExactly("2021-03-27 22:00-03:00", $tz);
        $actual = $cron->getMultipleRunDates(5, $dtCurrent, false, true, $tz->getName());
        foreach ($expected as $dtExpected) {
            $this->assertContainsEquals($dtExpected, $actual);
        }

        $dtCurrent = $this->createDateTimeExactly("2021-03-28 01:00-04:00", $tz);
        $actual = $cron->getMultipleRunDates(5, $dtCurrent, true, true, $tz->getName());
        foreach ($expected as $dtExpected) {
            $this->assertContainsEquals($dtExpected, $actual);
        }
    }

    public function testOffsetDecrementsMidnight(): void
    {
        $expression = '@hourly';
        $cron = new CronExpression($expression);
        $tz = new \DateTimeZone("America/Asuncion");

        $expected = [
            $this->createDateTimeExactly("2020-10-03 22:00-04:00", $tz),
            $this->createDateTimeExactly("2020-10-03 23:00-04:00", $tz),
            $this->createDateTimeExactly("2020-10-04 01:00-03:00", $tz),
            $this->createDateTimeExactly("2020-10-04 02:00-03:00", $tz),
            $this->createDateTimeExactly("2020-10-04 03:00-03:00", $tz),
        ];

        $dtCurrent = $this->createDateTimeExactly("2020-10-03 22:00-04:00", $tz);
        $actual = $cron->getMultipleRunDates(5, $dtCurrent, false, true, $tz->getName());
        foreach ($expected as $dtExpected) {
            $this->assertContainsEquals($dtExpected, $actual);
        }

        $dtCurrent = $this->createDateTimeExactly("2020-10-04 03:00-03:00", $tz);
        $actual = $cron->getMultipleRunDates(5, $dtCurrent, true, true, $tz->getName());
        foreach ($expected as $dtExpected) {
            $this->assertContainsEquals($dtExpected, $actual);
        }
    }
}
