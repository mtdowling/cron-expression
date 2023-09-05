<?php

declare(strict_types=1);

namespace Cron\Tests;

use Cron\CronExpression;
use Cron\MonthField;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use Webmozart\Assert\Assert;

/**
 * @author Michael Dowling <mtdowling@gmail.com>
 */
class CronExpressionTest extends TestCase
{
    /**
     * @covers \Cron\CronExpression::__construct
     */
    public function testConstructorRecognizesTemplates(): void
    {
        $this->assertSame('0 0 1 1 *', (new CronExpression('@annually'))->getExpression());
        $this->assertSame('0 0 1 1 *', (new CronExpression('@yearly'))->getExpression());
        $this->assertSame('0 0 * * 0', (new CronExpression('@weekly'))->getExpression());
        $this->assertSame('0 0 * * *', (new CronExpression('@daily'))->getExpression());
        $this->assertSame('0 0 * * *', (new CronExpression('@midnight'))->getExpression());
    }

    /**
     * @covers \Cron\CronExpression::__construct
     * @covers \Cron\CronExpression::getExpression
     * @covers \Cron\CronExpression::__toString
     */
    public function testParsesCronSchedule(): void
    {
        // '2010-09-10 12:00:00'
        $cron = new CronExpression('1 2-4 * 4,5,6 */3');
        $this->assertSame('1', $cron->getExpression(CronExpression::MINUTE));
        $this->assertSame('2-4', $cron->getExpression(CronExpression::HOUR));
        $this->assertSame('*', $cron->getExpression(CronExpression::DAY));
        $this->assertSame('4,5,6', $cron->getExpression(CronExpression::MONTH));
        $this->assertSame('*/3', $cron->getExpression(CronExpression::WEEKDAY));
        $this->assertSame('1 2-4 * 4,5,6 */3', $cron->getExpression());
        $this->assertSame('1 2-4 * 4,5,6 */3', (string) $cron);
        $this->assertNull($cron->getExpression('foo'));
    }

    /**
     * @covers \Cron\CronExpression::__construct
     * @covers \Cron\CronExpression::getExpression
     * @covers \Cron\CronExpression::__toString
     */
    public function testParsesCronScheduleThrowsAnException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid CRON field value A at position 0');
        new CronExpression('A 1 2 3 4');
    }

    /**
     * @covers \Cron\CronExpression::__construct
     * @covers \Cron\CronExpression::getExpression
     * @dataProvider scheduleWithDifferentSeparatorsProvider
     *
     * @param mixed $schedule
     */
    public function testParsesCronScheduleWithAnySpaceCharsAsSeparators($schedule, array $expected): void
    {
        $cron = new CronExpression($schedule);
        $this->assertSame($expected[0], $cron->getExpression(CronExpression::MINUTE));
        $this->assertSame($expected[1], $cron->getExpression(CronExpression::HOUR));
        $this->assertSame($expected[2], $cron->getExpression(CronExpression::DAY));
        $this->assertSame($expected[3], $cron->getExpression(CronExpression::MONTH));
        $this->assertSame($expected[4], $cron->getExpression(CronExpression::WEEKDAY));
    }

    /**
     * Data provider for testParsesCronScheduleWithAnySpaceCharsAsSeparators.
     *
     * @return array
     */
    public static function scheduleWithDifferentSeparatorsProvider(): array
    {
        return [
            ["*\t*\t*\t*\t*\t", ['*', '*', '*', '*', '*', '*']],
            ['*  *  *  *  *  ', ['*', '*', '*', '*', '*', '*']],
            ["* \t * \t * \t * \t * \t", ['*', '*', '*', '*', '*', '*']],
            ["*\t \t*\t \t*\t \t*\t \t*\t \t", ['*', '*', '*', '*', '*', '*']],
        ];
    }

    /**
     * @covers \Cron\CronExpression::__construct
     * @covers \Cron\CronExpression::setExpression
     * @covers \Cron\CronExpression::setPart
     */
    public function testInvalidCronsWillFail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // Only four values
        $cron = new CronExpression('* * * 1');
    }

    /**
     * @covers \Cron\CronExpression::setPart
     */
    public function testInvalidPartsWillFail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // Only four values
        $cron = new CronExpression('* * * * *');
        $cron->setPart(1, 'abc');
    }

    /**
     * Data provider for cron schedule.
     *
     * @return array
     */
    public function scheduleProvider(): array
    {
        return [
            ['*/2 */2 * * *', '2015-08-10 21:47:27', '2015-08-10 22:00:00', false],
            ['* * * * *', '2015-08-10 21:50:37', '2015-08-10 21:50:00', true],
            ['* 20,21,22 * * *', '2015-08-10 21:50:00', '2015-08-10 21:50:00', true],
            // Handles CSV values
            ['* 20,22 * * *', '2015-08-10 21:50:00', '2015-08-10 22:00:00', false],
            // CSV values can be complex
            ['7-9 * */9 * *', '2015-08-10 22:02:33', '2015-08-10 22:07:00', false],
            // 15th minute, of the second hour, every 15 days, in January, every Friday
            ['1 * * * 7', '2015-08-10 21:47:27', '2015-08-16 00:01:00', false],
            // Test with exact times
            ['47 21 * * *', strtotime('2015-08-10 21:47:30'), '2015-08-10 21:47:00', true],
            // Test Day of the week (issue #1)
            // According cron implementation, 0|7 = sunday, 1 => monday, etc
            ['* * * * 0', strtotime('2011-06-15 23:09:00'), '2011-06-19 00:00:00', false],
            ['* * * * 7', strtotime('2011-06-15 23:09:00'), '2011-06-19 00:00:00', false],
            ['* * * * 1', strtotime('2011-06-15 23:09:00'), '2011-06-20 00:00:00', false],
            // Should return the sunday date as 7 equals 0
            ['0 0 * * MON,SUN', strtotime('2011-06-15 23:09:00'), '2011-06-19 00:00:00', false],
            ['0 0 * * 1,7', strtotime('2011-06-15 23:09:00'), '2011-06-19 00:00:00', false],
            ['0 0 * * 0-4', strtotime('2011-06-15 23:09:00'), '2011-06-16 00:00:00', false],
            ['0 0 * * 7-4', strtotime('2011-06-15 23:09:00'), '2011-06-16 00:00:00', false],
            ['0 0 * * 4-7', strtotime('2011-06-15 23:09:00'), '2011-06-16 00:00:00', false],
            ['0 0 * * 7-3', strtotime('2011-06-15 23:09:00'), '2011-06-19 00:00:00', false],
            ['0 0 * * 3-7', strtotime('2011-06-15 23:09:00'), '2011-06-16 00:00:00', false],
            ['0 0 * * 3-7', strtotime('2011-06-18 23:09:00'), '2011-06-19 00:00:00', false],
            // Test lists of values and ranges (Abhoryo)
            ['0 0 * * 2-7', strtotime('2011-06-20 23:09:00'), '2011-06-21 00:00:00', false],
            ['0 0 * * 2-7', strtotime('2011-06-18 23:09:00'), '2011-06-19 00:00:00', false],
            ['0 0 * * 4-7', strtotime('2011-07-19 00:00:00'), '2011-07-21 00:00:00', false],
            // Test increments of ranges
            ['0-12/4 * * * *', strtotime('2011-06-20 12:04:00'), '2011-06-20 12:04:00', true],
            ['4-59/2 * * * *', strtotime('2011-06-20 12:04:00'), '2011-06-20 12:04:00', true],
            ['4-59/2 * * * *', strtotime('2011-06-20 12:06:00'), '2011-06-20 12:06:00', true],
            ['4-59/3 * * * *', strtotime('2011-06-20 12:06:00'), '2011-06-20 12:07:00', false],
            // Test Day of the Week and the Day of the Month (issue #1)
            ['0 0 1 1 0', strtotime('2011-06-15 23:09:00'), '2012-01-01 00:00:00', false],
            ['0 0 1 JAN 0', strtotime('2011-06-15 23:09:00'), '2012-01-01 00:00:00', false],
            ['0 0 1 * 0', strtotime('2011-06-15 23:09:00'), '2011-06-19 00:00:00', false],
            // Test the W day of the week modifier for day of the month field
            ['0 0 2W * *', strtotime('2011-07-01 00:00:00'), '2011-07-01 00:00:00', true],
            ['0 0 1W * *', strtotime('2011-05-01 00:00:00'), '2011-05-02 00:00:00', false],
            ['0 0 1W * *', strtotime('2011-07-01 00:00:00'), '2011-07-01 00:00:00', true],
            ['0 0 3W * *', strtotime('2011-07-01 00:00:00'), '2011-07-04 00:00:00', false],
            ['0 0 16W * *', strtotime('2011-07-01 00:00:00'), '2011-07-15 00:00:00', false],
            ['0 0 28W * *', strtotime('2011-07-01 00:00:00'), '2011-07-28 00:00:00', false],
            ['0 0 30W * *', strtotime('2011-07-01 00:00:00'), '2011-07-29 00:00:00', false],
            ['0 0 31W * *', strtotime('2011-07-01 00:00:00'), '2011-07-29 00:00:00', false],
            // Test the last weekday of a month
            ['* * * * 5L', strtotime('2011-07-01 00:00:00'), '2011-07-29 00:00:00', false],
            ['* * * * 6L', strtotime('2011-07-01 00:00:00'), '2011-07-30 00:00:00', false],
            ['* * * * 7L', strtotime('2011-07-01 00:00:00'), '2011-07-31 00:00:00', false],
            ['* * * * 1L', strtotime('2011-07-24 00:00:00'), '2011-07-25 00:00:00', false],
            ['* * * 1 5L', strtotime('2011-12-25 00:00:00'), '2012-01-27 00:00:00', false],
            // Test the hash symbol for the nth weekday of a given month
            ['* * * * 5#2', strtotime('2011-07-01 00:00:00'), '2011-07-08 00:00:00', false],
            ['* * * * 5#1', strtotime('2011-07-01 00:00:00'), '2011-07-01 00:00:00', true],
            ['* * * * 3#4', strtotime('2011-07-01 00:00:00'), '2011-07-27 00:00:00', false],

            // Issue #7, documented example failed
            ['3-59/15 6-12 */15 1 2-5', strtotime('2017-01-08 00:00:00'), '2017-01-10 06:03:00', false],

            // https://github.com/laravel/framework/commit/07d160ac3cc9764d5b429734ffce4fa311385403
            ['* * * * MON-FRI', strtotime('2017-01-08 00:00:00'), strtotime('2017-01-09 00:00:00'), false],
            ['* * * * TUE', strtotime('2017-01-08 00:00:00'), strtotime('2017-01-10 00:00:00'), false],

            // Issue #60, make sure that casing is less relevant for shortcuts, months, and days
            ['0 1 15 JUL mon,Wed,FRi', strtotime('2019-11-14 00:00:00'), strtotime('2020-07-01 01:00:00'), false],
            ['0 1 15 jul mon,Wed,FRi', strtotime('2019-11-14 00:00:00'), strtotime('2020-07-01 01:00:00'), false],
            ['@Weekly', strtotime('2019-11-14 00:00:00'), strtotime('2019-11-17 00:00:00'), false],
            ['@WEEKLY', strtotime('2019-11-14 00:00:00'), strtotime('2019-11-17 00:00:00'), false],
            ['@WeeklY', strtotime('2019-11-14 00:00:00'), strtotime('2019-11-17 00:00:00'), false],

            // Issue #76, DOW and DOM do not support ?
            ['0 12 * * ?', strtotime('2020-08-20 00:00:00'), strtotime('2020-08-20 12:00:00'), false],
            ['0 12 ? * *', strtotime('2020-08-20 00:00:00'), strtotime('2020-08-20 12:00:00'), false],
            ['0-59/59 10 * * *', strtotime('2021-08-25 10:00:00'), strtotime('2021-08-25 10:00:00'), true],
            ['0-59/59 10 * * *', strtotime('2021-08-25 09:00:00'), strtotime('2021-08-25 10:00:00'), false],
            ['0-59/59 10 * * *', strtotime('2021-08-25 10:01:00'), strtotime('2021-08-25 10:59:00'), false],
            ['0-59/65 10 * * *', strtotime('2021-08-25 10:01:00'), strtotime('2021-08-25 10:05:00'), false],
            ['41-59/24 5 * * *', strtotime('2021-08-25 10:00:00'), strtotime('2021-08-26 05:41:00'), false],
        ];
    }

    /**
     * @covers \Cron\CronExpression::isDue
     * @covers \Cron\CronExpression::getNextRunDate
     * @covers \Cron\DayOfMonthField
     * @covers \Cron\DayOfWeekField
     * @covers \Cron\MinutesField
     * @covers \Cron\HoursField
     * @covers \Cron\MonthField
     * @covers \Cron\CronExpression::getRunDate
     * @dataProvider scheduleProvider
     *
     * @param mixed $schedule
     * @param mixed $relativeTime
     * @param mixed $nextRun
     * @param mixed $isDue
     */
    public function testDeterminesIfCronIsDue($schedule, $relativeTime, $nextRun, $isDue): void
    {
        // Test next run date
        $cron = new CronExpression($schedule);
        if (\is_string($relativeTime)) {
            $relativeTime = new DateTime($relativeTime);
        } elseif (\is_int($relativeTime)) {
            $relativeTime = date('Y-m-d H:i:s', $relativeTime);
        }

        $nextRunDate = new DateTime();
        if (\is_string($nextRun)) {
            $nextRunDate = new DateTime($nextRun);
        } elseif (\is_int($nextRun)) {
            $nextRunDate = new DateTime();
            $nextRunDate->setTimestamp($nextRun);
        }
        $this->assertSame($isDue, $cron->isDue($relativeTime));
        $next = $cron->getNextRunDate($relativeTime, 0, true);

        $this->assertEquals($nextRunDate, $next);
    }

    /**
     * @covers \Cron\CronExpression::isDue
     */
    public function testIsDueHandlesDifferentDates(): void
    {
        $cron = new CronExpression('* * * * *');
        $this->assertTrue($cron->isDue());
        $this->assertTrue($cron->isDue('now'));
        $this->assertTrue($cron->isDue(new DateTime('now')));
        $this->assertTrue($cron->isDue(date('Y-m-d H:i')));
        $this->assertTrue($cron->isDue(new DateTimeImmutable('now')));
    }

    /**
     * @covers \Cron\CronExpression::isDue
     */
    public function testIsDueHandlesDifferentDefaultTimezones(): void
    {
        $originalTimezone = date_default_timezone_get();
        $cron = new CronExpression('0 15 * * 3'); //Wednesday at 15:00
        $date = '2014-01-01 15:00'; //Wednesday

        date_default_timezone_set('UTC');
        $this->assertTrue($cron->isDue(new DateTime($date), 'UTC'));
        $this->assertFalse($cron->isDue(new DateTime($date), 'Europe/Amsterdam'));
        $this->assertFalse($cron->isDue(new DateTime($date), 'Asia/Tokyo'));

        date_default_timezone_set('Europe/Amsterdam');
        $this->assertFalse($cron->isDue(new DateTime($date), 'UTC'));
        $this->assertTrue($cron->isDue(new DateTime($date), 'Europe/Amsterdam'));
        $this->assertFalse($cron->isDue(new DateTime($date), 'Asia/Tokyo'));

        date_default_timezone_set('Asia/Tokyo');
        $this->assertFalse($cron->isDue(new DateTime($date), 'UTC'));
        $this->assertFalse($cron->isDue(new DateTime($date), 'Europe/Amsterdam'));
        $this->assertTrue($cron->isDue(new DateTime($date), 'Asia/Tokyo'));

        date_default_timezone_set($originalTimezone);
    }

    /**
     * @covers \Cron\CronExpression::isDue
     */
    public function testIsDueHandlesDifferentSuppliedTimezones(): void
    {
        $cron = new CronExpression('0 15 * * 3'); //Wednesday at 15:00
        $date = '2014-01-01 15:00'; //Wednesday

        $this->assertTrue($cron->isDue(new DateTime($date, new DateTimeZone('UTC')), 'UTC'));
        $this->assertFalse($cron->isDue(new DateTime($date, new DateTimeZone('UTC')), 'Europe/Amsterdam'));
        $this->assertFalse($cron->isDue(new DateTime($date, new DateTimeZone('UTC')), 'Asia/Tokyo'));

        $this->assertFalse($cron->isDue(new DateTime($date, new DateTimeZone('Europe/Amsterdam')), 'UTC'));
        $this->assertTrue($cron->isDue(new DateTime($date, new DateTimeZone('Europe/Amsterdam')), 'Europe/Amsterdam'));
        $this->assertFalse($cron->isDue(new DateTime($date, new DateTimeZone('Europe/Amsterdam')), 'Asia/Tokyo'));

        $this->assertFalse($cron->isDue(new DateTime($date, new DateTimeZone('Asia/Tokyo')), 'UTC'));
        $this->assertFalse($cron->isDue(new DateTime($date, new DateTimeZone('Asia/Tokyo')), 'Europe/Amsterdam'));
        $this->assertTrue($cron->isDue(new DateTime($date, new DateTimeZone('Asia/Tokyo')), 'Asia/Tokyo'));
    }

    /**
     * @covers \Cron\CronExpression::isDue
     */
    public function testIsDueHandlesDifferentTimezonesAsArgument(): void
    {
        $cron = new CronExpression('0 15 * * 3'); //Wednesday at 15:00
        $date = '2014-01-01 15:00'; //Wednesday
        $utc = new \DateTimeZone('UTC');
        $amsterdam = new \DateTimeZone('Europe/Amsterdam');
        $tokyo = new \DateTimeZone('Asia/Tokyo');
        $this->assertTrue($cron->isDue(new DateTime($date, $utc), 'UTC'));
        $this->assertFalse($cron->isDue(new DateTime($date, $amsterdam), 'UTC'));
        $this->assertFalse($cron->isDue(new DateTime($date, $tokyo), 'UTC'));
        $this->assertFalse($cron->isDue(new DateTime($date, $utc), 'Europe/Amsterdam'));
        $this->assertTrue($cron->isDue(new DateTime($date, $amsterdam), 'Europe/Amsterdam'));
        $this->assertFalse($cron->isDue(new DateTime($date, $tokyo), 'Europe/Amsterdam'));
        $this->assertFalse($cron->isDue(new DateTime($date, $utc), 'Asia/Tokyo'));
        $this->assertFalse($cron->isDue(new DateTime($date, $amsterdam), 'Asia/Tokyo'));
        $this->assertTrue($cron->isDue(new DateTime($date, $tokyo), 'Asia/Tokyo'));
    }

    /**
     * @covers \Cron\CronExpression::isDue
     */
    public function testRecognisesTimezonesAsPartOfDateTime(): void
    {
        $cron = new CronExpression('0 7 * * *');
        $tzCron = 'America/New_York';
        $tzServer = new \DateTimeZone('Europe/London');

        $dtCurrent = \DateTime::createFromFormat('!Y-m-d H:i:s', '2017-10-17 10:00:00', $tzServer);
        Assert::isInstanceOf($dtCurrent, DateTime::class);
        $dtPrev = $cron->getPreviousRunDate($dtCurrent, 0, true, $tzCron);
        $this->assertEquals('1508151600 : 2017-10-16T07:00:00-04:00 : America/New_York', $dtPrev->format('U \\: c \\: e'));

        $dtCurrent = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', '2017-10-17 10:00:00', $tzServer);
        Assert::isInstanceOf($dtCurrent, \DateTimeImmutable::class);
        $dtPrev = $cron->getPreviousRunDate($dtCurrent, 0, true, $tzCron);
        $this->assertEquals('1508151600 : 2017-10-16T07:00:00-04:00 : America/New_York', $dtPrev->format('U \\: c \\: e'));

        $dtCurrent = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', '2017-10-17 10:00:00', $tzServer);
        Assert::isInstanceOf($dtCurrent, \DateTimeImmutable::class);
        $dtPrev = $cron->getPreviousRunDate($dtCurrent->format('c'), 0, true, $tzCron);
        $this->assertEquals('1508151600 : 2017-10-16T07:00:00-04:00 : America/New_York', $dtPrev->format('U \\: c \\: e'));

        $dtCurrent = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', '2017-10-17 10:00:00', $tzServer);
        Assert::isInstanceOf($dtCurrent, \DateTimeImmutable::class);
        $dtPrev = $cron->getPreviousRunDate($dtCurrent->format('\\@U'), 0, true, $tzCron);
        $this->assertEquals('1508151600 : 2017-10-16T07:00:00-04:00 : America/New_York', $dtPrev->format('U \\: c \\: e'));
    }

    /**
     * @covers \Cron\CronExpression::getPreviousRunDate
     */
    public function testCanGetPreviousRunDates(): void
    {
        $cron = new CronExpression('* * * * *');
        $next = $cron->getNextRunDate('now');
        $two = $cron->getNextRunDate('now', 1);
        $this->assertEquals($next, $cron->getPreviousRunDate($two));

        $cron = new CronExpression('* */2 * * *');
        $next = $cron->getNextRunDate('now');
        $two = $cron->getNextRunDate('now', 1);
        $this->assertEquals($next, $cron->getPreviousRunDate($two));

        $cron = new CronExpression('* * * */2 *');
        $next = $cron->getNextRunDate('now');
        $two = $cron->getNextRunDate('now', 1);
        $this->assertEquals($next, $cron->getPreviousRunDate($two));
    }

    /**
     * @covers \Cron\CronExpression::getMultipleRunDates
     */
    public function testProvidesMultipleRunDates(): void
    {
        $cron = new CronExpression('*/2 * * * *');
        $this->assertEquals([
            new DateTime('2008-11-09 00:00:00'),
            new DateTime('2008-11-09 00:02:00'),
            new DateTime('2008-11-09 00:04:00'),
            new DateTime('2008-11-09 00:06:00'),
        ], $cron->getMultipleRunDates(4, '2008-11-09 00:00:00', false, true));
    }

    /**
     * @covers \Cron\CronExpression::getMultipleRunDates
     * @covers \Cron\CronExpression::setMaxIterationCount
     */
    public function testProvidesMultipleRunDatesForTheFarFuture(): void
    {
        // Fails with the default 1000 iteration limit
        $cron = new CronExpression('0 0 12 1 *');
        $cron->setMaxIterationCount(2000);
        $this->assertEquals([
            new DateTime('2016-01-12 00:00:00'),
            new DateTime('2017-01-12 00:00:00'),
            new DateTime('2018-01-12 00:00:00'),
            new DateTime('2019-01-12 00:00:00'),
            new DateTime('2020-01-12 00:00:00'),
            new DateTime('2021-01-12 00:00:00'),
            new DateTime('2022-01-12 00:00:00'),
            new DateTime('2023-01-12 00:00:00'),
            new DateTime('2024-01-12 00:00:00'),
        ], $cron->getMultipleRunDates(9, '2015-04-28 00:00:00', false, true));
    }

    /**
     * @covers \Cron\CronExpression
     */
    public function testCanIterateOverNextRuns(): void
    {
        $cron = new CronExpression('@weekly');
        $nextRun = $cron->getNextRunDate('2008-11-09 08:00:00');
        $this->assertEquals($nextRun, new DateTime('2008-11-16 00:00:00'));

        // You can iterate over them
        $nextRun = $cron->getNextRunDate($cron->getNextRunDate('2008-11-09 00:00:00', 1, true), 1, true);
        $this->assertEquals($nextRun, new DateTime('2008-11-23 00:00:00'));

        // You can skip more than one
        $nextRun = $cron->getNextRunDate('2008-11-09 00:00:00', 2, true);
        $this->assertEquals($nextRun, new DateTime('2008-11-23 00:00:00'));
        $nextRun = $cron->getNextRunDate('2008-11-09 00:00:00', 3, true);
        $this->assertEquals($nextRun, new DateTime('2008-11-30 00:00:00'));
    }

    /**
     * @covers \Cron\CronExpression::getRunDate
     */
    public function testGetRunDateHandlesDifferentDates(): void
    {
        $cron = new CronExpression('@weekly');
        $date = new DateTime("2019-03-10 00:00:00");
        $this->assertEquals($date, $cron->getNextRunDate("2019-03-03 08:00:00"));
        $this->assertEquals($date, $cron->getNextRunDate(new DateTime("2019-03-03 08:00:00")));
        $this->assertEquals($date, $cron->getNextRunDate(new DateTimeImmutable("2019-03-03 08:00:00")));
    }

    /**
     * If both day of month and day of week are set in an expression,
     * we have to return a date which among dates matching either of two criteria is closest to the current date.
     *
     * Previously the earliest of dates was always returned, which was incorrect for previous run date.
     *
     * @covers \Cron\CronExpression::getRunDate
     */
    public function testGetRunDateHandlesSimultaneousDayOfMonthAndDayOfWeek(): void
    {
        $cron = new CronExpression('0 0 13 * 3');
        $date = new DateTime("2021-07-15 00:00:00");
        $this->assertEquals(new DateTime("2021-07-21 00:00:00"), $cron->getNextRunDate($date));
        $this->assertEquals(new DateTime("2021-07-14 00:00:00"), $cron->getPreviousRunDate($date));
    }

    /**
     * @covers \Cron\CronExpression::getRunDate
     */
    public function testSkipsCurrentDateByDefault(): void
    {
        $cron = new CronExpression('* * * * *');
        $current = new DateTime('now');
        $next = $cron->getNextRunDate($current);
        $nextPrev = $cron->getPreviousRunDate($next);
        $this->assertSame($current->format('Y-m-d H:i:00'), $nextPrev->format('Y-m-d H:i:s'));
    }

    /**
     * @covers \Cron\CronExpression::getRunDate
     * @ticket 7
     */
    public function testStripsForSeconds(): void
    {
        $cron = new CronExpression('* * * * *');
        $current = new DateTime('2011-09-27 10:10:54');
        $this->assertSame('2011-09-27 10:11:00', $cron->getNextRunDate($current)->format('Y-m-d H:i:s'));
    }

    /**
     * @covers \Cron\CronExpression::getRunDate
     */
    public function testFixesPhpBugInDateIntervalMonth(): void
    {
        $cron = new CronExpression('0 0 27 JAN *');
        $this->assertSame('2011-01-27 00:00:00', $cron->getPreviousRunDate('2011-08-22 00:00:00')->format('Y-m-d H:i:s'));
    }

    public function testIssue29(): void
    {
        $cron = new CronExpression('@weekly');
        $this->assertSame(
            '2013-03-10 00:00:00',
            $cron->getPreviousRunDate('2013-03-17 00:00:00')->format('Y-m-d H:i:s')
        );
    }

    /**
     * @see https://github.com/mtdowling/cron-expression/issues/20
     */
    public function testIssue20(): void
    {
        $e = new CronExpression('* * * * MON#1');
        $this->assertTrue($e->isDue(new DateTime('2014-04-07 00:00:00')));
        $this->assertFalse($e->isDue(new DateTime('2014-04-14 00:00:00')));
        $this->assertFalse($e->isDue(new DateTime('2014-04-21 00:00:00')));

        $e->setExpression('* * * * SAT#2');
        $this->assertFalse($e->isDue(new DateTime('2014-04-05 00:00:00')));
        $this->assertTrue($e->isDue(new DateTime('2014-04-12 00:00:00')));
        $this->assertFalse($e->isDue(new DateTime('2014-04-19 00:00:00')));

        $e->setExpression('* * * * SUN#3');
        $this->assertFalse($e->isDue(new DateTime('2014-04-13 00:00:00')));
        $this->assertTrue($e->isDue(new DateTime('2014-04-20 00:00:00')));
        $this->assertFalse($e->isDue(new DateTime('2014-04-27 00:00:00')));
    }

    /**
     * @covers \Cron\CronExpression::getRunDate
     */
    public function testKeepOriginalTime(): void
    {
        $now = new \DateTime();
        $strNow = $now->format(DateTime::ISO8601);
        $cron = new CronExpression('0 0 * * *');
        $cron->getPreviousRunDate($now);
        $this->assertSame($strNow, $now->format(DateTime::ISO8601));
    }

    /**
     * @covers \Cron\CronExpression::__construct
     * @covers \Cron\CronExpression::isValidExpression
     * @covers \Cron\CronExpression::setExpression
     * @covers \Cron\CronExpression::setPart
     */
    public function testValidationWorks(): void
    {
        // Invalid. Only four values
        $this->assertFalse(CronExpression::isValidExpression('* * * 1'));
        // Valid
        $this->assertTrue(CronExpression::isValidExpression('* * * * 1'));

        // Issue #156, 13 is an invalid month
        $this->assertFalse(CronExpression::isValidExpression('* * * 13 * '));

        // Issue #155, 90 is an invalid second
        $this->assertFalse(CronExpression::isValidExpression('90 * * * *'));

        // Issue #154, 24 is an invalid hour
        $this->assertFalse(CronExpression::isValidExpression('0 24 1 12 0'));

        // Issue #125, this is just all sorts of wrong
        $this->assertFalse(CronExpression::isValidExpression('990 14 * * mon-fri0345345'));

        // Issue #137, multiple question marks are not allowed
        $this->assertFalse(CronExpression::isValidExpression('0 8 ? * ?'));
        // Question marks are only allowed in dom and dow part
        $this->assertFalse(CronExpression::isValidExpression('? * * * *'));
        $this->assertFalse(CronExpression::isValidExpression('* ? * * *'));
        $this->assertFalse(CronExpression::isValidExpression('* * * ? *'));

        // see https://github.com/dragonmantank/cron-expression/issues/5
        $this->assertTrue(CronExpression::isValidExpression('2,17,35,47 5-7,11-13 * * *'));
    }

    /**
     * Makes sure that 00 is considered a valid value for 0-based fields
     * cronie allows numbers with a leading 0, so adding support for this as well.
     *
     * @see https://github.com/dragonmantank/cron-expression/issues/12
     */
    public function testDoubleZeroIsValid(): void
    {
        $this->assertTrue(CronExpression::isValidExpression('00 * * * *'));
        $this->assertTrue(CronExpression::isValidExpression('01 * * * *'));
        $this->assertTrue(CronExpression::isValidExpression('* 00 * * *'));
        $this->assertTrue(CronExpression::isValidExpression('* 01 * * *'));

        $e = new CronExpression('00 * * * *');
        $this->assertTrue($e->isDue(new DateTime('2014-04-07 00:00:00')));
        $e->setExpression('01 * * * *');
        $this->assertTrue($e->isDue(new DateTime('2014-04-07 00:01:00')));

        $e->setExpression('* 00 * * *');
        $this->assertTrue($e->isDue(new DateTime('2014-04-07 00:00:00')));
        $e->setExpression('* 01 * * *');
        $this->assertTrue($e->isDue(new DateTime('2014-04-07 01:00:00')));
    }

    /**
     * Ranges with large steps should "wrap around" to the appropriate value
     * cronie allows for steps that are larger than the range of a field, with it wrapping around like a ring buffer. We
     * should do the same.
     *
     * @see https://github.com/dragonmantank/cron-expression/issues/6
     */
    public function testRangesWrapAroundWithLargeSteps(): void
    {
        $f = new MonthField();
        $this->assertTrue($f->validate('*/123'));
        $this->assertSame([4], $f->getRangeForExpression('*/123', 12));

        $e = new CronExpression('* * * */123 *');
        $this->assertTrue($e->isDue(new DateTime('2014-04-07 00:00:00')));

        $nextRunDate = $e->getNextRunDate(new DateTime('2014-04-07 00:00:00'));
        $this->assertSame('2014-04-07 00:01:00', $nextRunDate->format('Y-m-d H:i:s'));

        $nextRunDate = $e->getNextRunDate(new DateTime('2014-05-07 00:00:00'));
        $this->assertSame('2015-04-01 00:00:00', $nextRunDate->format('Y-m-d H:i:s'));
    }

    /**
     * When there is an issue with a field, we should report the human readable position.
     *
     * @see https://github.com/dragonmantank/cron-expression/issues/29
     */
    public function testFieldPositionIsHumanAdjusted(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('6 is not a valid position');

        $e = new CronExpression('0 * * * * ? *');
    }

    /**
     * @see https://github.com/dragonmantank/cron-expression/issues/35
     */
    public function testMakeDayOfWeekAnOrSometimes(): void
    {
        $cron = new CronExpression('30 0 1 * 1');
        $runs = $cron->getMultipleRunDates(5, date("2019-10-10 23:20:00"), false, true);

        $this->assertSame("2019-10-14 00:30:00", $runs[0]->format('Y-m-d H:i:s'));
        $this->assertSame("2019-10-21 00:30:00", $runs[1]->format('Y-m-d H:i:s'));
        $this->assertSame("2019-10-28 00:30:00", $runs[2]->format('Y-m-d H:i:s'));
        $this->assertSame("2019-11-01 00:30:00", $runs[3]->format('Y-m-d H:i:s'));
        $this->assertSame("2019-11-04 00:30:00", $runs[4]->format('Y-m-d H:i:s'));
    }

    /**
     * Make sure that getNextRunDate() does not add arbitrary minutes
     *
     * @see https://github.com/mtdowling/cron-expression/issues/152
     */
    public function testNextRunDateShouldNotAddMinutes(): void
    {
        $e = new CronExpression('* 19 * * *');
        $tz = new \DateTimeZone("Europe/London");
        $dt = new \DateTimeImmutable("2021-05-31 18:15:00", $tz);
        $nextRunDate = $e->getNextRunDate($dt);

        $this->assertSame("00", $nextRunDate->format("i"));
    }

    /**
     * Tests the getParts function.
     */
    public function testGetParts(): void
    {
        $e = CronExpression::factory('0 22 * * 1-5');
        $parts = $e->getParts();

        $this->assertSame('0', $parts[0]);
        $this->assertSame('22', $parts[1]);
        $this->assertSame('*', $parts[2]);
        $this->assertSame('*', $parts[3]);
        $this->assertSame('1-5', $parts[4]);
    }

    public function testBerlinShouldAdvanceProperlyOverDST()
    {
        $e = new CronExpression('0 0 1 * *');
        $expected = new \DateTime('2022-11-01 00:00:00', new \DateTimeZone('Europe/Berlin'));
        $next = $e->getNextRunDate(new \DateTime('2022-10-30', new \DateTimeZone('Europe/Berlin')));
        $this->assertEquals($expected, $next);
    }

    /**
     * Helps validate additional test cases that were failing as part of #131's fix
     *
     * @see https://github.com/dragonmantank/cron-expression/issues/131
     */
    public function testIssue131()
    {
        $e = new CronExpression('* * * * 2');
        $expected = new \DateTime('2020-10-27 00:00:00', new \DateTimeZone('Europe/Berlin'));
        $next = $e->getNextRunDate(new DateTime('2020-10-23 15:31:45', new \DateTimeZone('Europe/Berlin')));
        $this->assertEquals($expected, $next);

        $expected = new \DateTime('2020-10-20 23:59:00', new \DateTimeZone('Europe/Berlin'));
        $prev = $e->getPreviousRunDate(new DateTime('2020-10-23 15:31:45', new \DateTimeZone('Europe/Berlin')));
        $this->assertEquals($expected, $prev);

        $e = new CronExpression('15 1 1 9,11 *');
        $expected = new \DateTime('2022-09-01 01:15:00');
        $next = $e->getNextRunDate(new \DateTime('2022-08-20 03:44:02'));
        $this->assertEquals($expected, $next);

        $expected = new \DateTime('2021-11-01 01:15:00');
        $prev = $e->getPreviousRunDate(new \DateTime('2022-08-20 03:44:02'));
        $this->assertEquals($expected, $prev);
    }

    public function testIssue128()
    {
        $e = new CronExpression('0 20 L 6,12 ?');
        $expected = new \DateTime('2022-12-31 20:00:00');
        $next = $e->getNextRunDate(new \DateTime('2022-08-20 03:44:02'));
        $this->assertEquals($expected, $next);

        $expected = new \DateTime('2023-12-31 20:00:00');
        $next = $e->getNextRunDate(new \DateTime('2022-08-20 03:44:02'), 2);
        $this->assertEquals($expected, $next);

        $expected = new \DateTime('2022-06-30 20:00:00');
        $prev = $e->getPreviousRunDate(new \DateTime('2022-08-20 03:44:02'));
        $this->assertEquals($expected, $prev);

        $expected = new \DateTime('2021-12-31 20:00:00');
        $prev = $e->getPreviousRunDate(new \DateTime('2022-08-20 03:44:02'), 1);
        $this->assertEquals($expected, $prev);

        $e = new CronExpression('0 20 L 6,12 0-6');
        $expected = new \DateTime('2022-12-01 20:00:00');
        $next = $e->getNextRunDate(new \DateTime('2022-08-20 03:44:02'));
        $this->assertEquals($expected, $next);

        $e = new CronExpression('0 20 L 6,12 0-6');
        $expected = new \DateTime('2022-12-02 20:00:00');
        $next = $e->getNextRunDate(new \DateTime('2022-08-20 03:44:02'), 1);
        $this->assertEquals($expected, $next);

        $e = new CronExpression('0 20 L 6,12 0-6');
        $expected = new \DateTime('2022-12-03 20:00:00');
        $next = $e->getNextRunDate(new \DateTime('2022-08-20 03:44:02'), 2);
        $this->assertEquals($expected, $next);

        $e = new CronExpression('0 20 * 6,12 *');
        $expected = new \DateTime('2022-12-01 20:00:00');
        $next = $e->getNextRunDate(new \DateTime('2022-08-20 03:44:02'));
        $this->assertEquals($expected, $next);

        $e = new CronExpression('0 20 * 6,12 *');
        $expected = new \DateTime('2022-12-06 20:00:00');
        $next = $e->getNextRunDate(new \DateTime('2022-08-20 03:44:02'), 5);
        $this->assertEquals($expected, $next);
    }

    public function testItCanRegisterAnValidExpression(): void
    {
        CronExpression::registerAlias('@every', '* * * * *');

        self::assertCount(8, CronExpression::getAliases());
        self::assertArrayHasKey('@every', CronExpression::getAliases());
        self::assertTrue(CronExpression::supportsAlias('@every'));
        self::assertEquals(new CronExpression('@every'), new CronExpression('* * * * *'));

        self::assertTrue(CronExpression::unregisterAlias('@every'));
        self::assertFalse(CronExpression::unregisterAlias('@every'));

        self::assertCount(7, CronExpression::getAliases());
        self::assertArrayNotHasKey('@every', CronExpression::getAliases());
        self::assertFalse(CronExpression::supportsAlias('@every'));

        $this->expectException(LogicException::class);
        new CronExpression('@every');
    }

    public function testItWillFailToRegisterAnInvalidExpression(): void
    {
        $this->expectException(LogicException::class);

        CronExpression::registerAlias('@every', 'foobar');
    }

    public function testItWillFailToRegisterAnInvalidName(): void
    {
        $this->expectException(LogicException::class);

        CronExpression::registerAlias('every', '* * * * *');
    }

    public function testItWillFailToRegisterAnInvalidName2(): void
    {
        $this->expectException(LogicException::class);

        CronExpression::registerAlias('@évery', '* * * * *');
    }

    public function testItWillFailToRegisterAValidNameTwice(): void
    {
        CronExpression::registerAlias('@Ev_eR_y', '* * * * *');

        $this->expectException(LogicException::class);
        CronExpression::registerAlias('@eV_Er_Y', '2 2 2 2 2');
    }

    public function testItWillFailToUnregisterADefaultExpression(): void
    {
        $this->expectException(LogicException::class);

        CronExpression::unregisterAlias('@daily');
    }

    public function testIssue134ForeachInvalidArgumentOnHours()
    {
        $cron = new CronExpression('0 0 1 1 *');
        $prev = $cron->getPreviousRunDate(new \DateTimeImmutable('2021-09-07T09:36:00Z'));
        $this->assertEquals(new \DateTime('2021-01-01 00:00:00'), $prev);
    }

    public function testIssue151ExpressionSupportLW()
    {
        $cron = new CronExpression('0 10 LW * *');
        $this->assertTrue($cron->isDue(new \DateTimeImmutable('2023-08-31 10:00:00')));
        $this->assertFalse($cron->isDue(new \DateTimeImmutable('2023-08-30 10:00:00')));
    }
}
