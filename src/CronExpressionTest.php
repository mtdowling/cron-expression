<?php

declare(strict_types=1);

namespace Cron;

use DateTime;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @author Michael Dowling <mtdowling@gmail.com>
 */
final class CronExpressionTest extends TestCase
{
    /**
     * @covers \Cron\CronExpression::fromString
     */
    public function testFactoryRecognizesTemplates(): void
    {
        self::assertSame('0 0 1 1 *', CronExpression::fromString('@annually')->getExpression());
        self::assertSame('0 0 1 1 *', CronExpression::fromString('@yearly')->getExpression());
        self::assertSame('0 0 * * 0', CronExpression::fromString('@weekly')->getExpression());
    }

    /**
     * @covers \Cron\CronExpression::__construct
     * @covers \Cron\CronExpression::getExpression
     * @covers \Cron\CronExpression::__toString
     */
    public function testParsesCronSchedule(): void
    {
        // '2010-09-10 12:00:00'
        $cron = CronExpression::fromString('1 2-4 * 4,5,6 */3');
        self::assertSame('1', $cron->getExpression(CronExpression::MINUTE));
        self::assertSame('2-4', $cron->getExpression(CronExpression::HOUR));
        self::assertSame('*', $cron->getExpression(CronExpression::DAY));
        self::assertSame('4,5,6', $cron->getExpression(CronExpression::MONTH));
        self::assertSame('*/3', $cron->getExpression(CronExpression::WEEKDAY));
        self::assertSame('1 2-4 * 4,5,6 */3', $cron->getExpression());
        self::assertSame('1 2-4 * 4,5,6 */3', (string) $cron);
        self::assertNull($cron->getExpression('foo'));
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

        CronExpression::fromString('A 1 2 3 4');
    }

    /**
     * @covers \Cron\CronExpression::__construct
     * @covers \Cron\CronExpression::getExpression
     * @dataProvider scheduleWithDifferentSeparatorsProvider
     */
    public function testParsesCronScheduleWithAnySpaceCharsAsSeparators(string $schedule, array $expected): void
    {
        $cron = CronExpression::fromString($schedule);
        self::assertSame($expected[0], $cron->getExpression(CronExpression::MINUTE));
        self::assertSame($expected[1], $cron->getExpression(CronExpression::HOUR));
        self::assertSame($expected[2], $cron->getExpression(CronExpression::DAY));
        self::assertSame($expected[3], $cron->getExpression(CronExpression::MONTH));
        self::assertSame($expected[4], $cron->getExpression(CronExpression::WEEKDAY));
    }

    /**
     * Data provider for testParsesCronScheduleWithAnySpaceCharsAsSeparators.
     *
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
     * @covers \Cron\CronExpression::setPart
     */
    public function testInvalidCronsWillFail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // Only four values
        CronExpression::fromString('* * * 1');
    }

    /**
     * @covers \Cron\CronExpression::setPart
     */
    public function testInvalidPartsWillFail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // Only four values
        CronExpression::fromString('* * abc * *');
    }

    /**
     * Data provider for cron schedule.
     *
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
            ['0 0 1 * 0', strtotime('2011-06-15 23:09:00'), '2012-01-01 00:00:00', false],
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
     */
    public function testDeterminesIfCronIsDue(string $schedule, string|int $relativeTime, string $nextRun, bool $isDue): void
    {
        // Test next run date
        $cron = CronExpression::fromString($schedule);
        if (is_string($relativeTime)) {
            $relativeTime = new DateTime($relativeTime);
        } else {
            $relativeTime = date('Y-m-d H:i:s', $relativeTime);
        }
        self::assertSame($isDue, $cron->isDue($relativeTime));
        $next = $cron->getNextRunDate($relativeTime, 0, true);
        self::assertEquals(new DateTime($nextRun), $next);
    }

    /**
     * @covers \Cron\CronExpression::isDue
     */
    public function testIsDueHandlesDifferentDates(): void
    {
        $cron = CronExpression::fromString('* * * * *');
        self::assertTrue($cron->isDue());
        self::assertTrue($cron->isDue('now'));
        self::assertTrue($cron->isDue(new DateTime('now')));
        self::assertTrue($cron->isDue(date('Y-m-d H:i')));
    }

    /**
     * @covers \Cron\CronExpression::isDue
     */
    public function testIsDueHandlesDifferentTimezones(): void
    {
        $cron = CronExpression::fromString('0 15 * * 3'); //Wednesday at 15:00
        $date = '2014-01-01 15:00'; //Wednesday
        $utc = new DateTimeZone('UTC');
        $amsterdam =  new DateTimeZone('Europe/Amsterdam');
        $tokyo = new DateTimeZone('Asia/Tokyo');

        date_default_timezone_set('UTC');
        self::assertTrue($cron->isDue(new DateTime($date, $utc)));
        self::assertFalse($cron->isDue(new DateTime($date, $amsterdam)));
        self::assertFalse($cron->isDue(new DateTime($date, $tokyo)));

        date_default_timezone_set('Europe/Amsterdam');
        self::assertFalse($cron->isDue(new DateTime($date, $utc)));
        self::assertTrue($cron->isDue(new DateTime($date, $amsterdam)));
        self::assertFalse($cron->isDue(new DateTime($date, $tokyo)));

        date_default_timezone_set('Asia/Tokyo');
        self::assertFalse($cron->isDue(new DateTime($date, $utc)));
        self::assertFalse($cron->isDue(new DateTime($date, $amsterdam)));
        self::assertTrue($cron->isDue(new DateTime($date, $tokyo)));
    }

    /**
      * @covers Cron\CronExpression::isDue
      */
    public function testIsDueHandlesDifferentTimezonesAsArgument(): void
    {
        $cron      = CronExpression::fromString('0 15 * * 3'); //Wednesday at 15:00
        $date      = '2014-01-01 15:00'; //Wednesday
        $utc       = new DateTimeZone('UTC');
        $amsterdam = new DateTimeZone('Europe/Amsterdam');
        $tokyo     = new DateTimeZone('Asia/Tokyo');
        self::assertTrue($cron->isDue(new DateTime($date, $utc), 'UTC'));
        self::assertFalse($cron->isDue(new DateTime($date, $amsterdam), 'UTC'));
        self::assertFalse($cron->isDue(new DateTime($date, $tokyo), 'UTC'));
        self::assertFalse($cron->isDue(new DateTime($date, $utc), 'Europe/Amsterdam'));
        self::assertTrue($cron->isDue(new DateTime($date, $amsterdam), 'Europe/Amsterdam'));
        self::assertFalse($cron->isDue(new DateTime($date, $tokyo), 'Europe/Amsterdam'));
        self::assertFalse($cron->isDue(new DateTime($date, $utc), 'Asia/Tokyo'));
        self::assertFalse($cron->isDue(new DateTime($date, $amsterdam), 'Asia/Tokyo'));
        self::assertTrue($cron->isDue(new DateTime($date, $tokyo), 'Asia/Tokyo'));
    }

    /**
     * @covers \Cron\CronExpression::getPreviousRunDate
     */
    public function testCanGetPreviousRunDates(): void
    {
        $cron = CronExpression::fromString('* * * * *');
        $next = $cron->getNextRunDate('now');
        $two = $cron->getNextRunDate('now', 1);
        self::assertEquals($next, $cron->getPreviousRunDate($two));

        $cron = CronExpression::fromString('* */2 * * *');
        $next = $cron->getNextRunDate('now');
        $two = $cron->getNextRunDate('now', 1);
        self::assertEquals($next, $cron->getPreviousRunDate($two));

        $cron = CronExpression::fromString('* * * */2 *');
        $next = $cron->getNextRunDate('now');
        $two = $cron->getNextRunDate('now', 1);
        self::assertEquals($next, $cron->getPreviousRunDate($two));
    }

    /**
     * @covers \Cron\CronExpression::getMultipleRunDates
     */
    public function testProvidesMultipleRunDates(): void
    {
        $cron = CronExpression::fromString('*/2 * * * *');
        $result = $cron->getMultipleRunDates(4, '2008-11-09 00:00:00', false, true);

        self::assertEquals([
            new DateTime('2008-11-09 00:00:00'),
            new DateTime('2008-11-09 00:02:00'),
            new DateTime('2008-11-09 00:04:00'),
            new DateTime('2008-11-09 00:06:00'),
        ], iterator_to_array($result, false));
    }

    /**
     * @covers \Cron\CronExpression::getMultipleRunDates
     * @covers \Cron\CronExpression::setMaxIterationCount
     */
    public function testProvidesMultipleRunDatesForTheFarFuture(): void
    {
        // Fails with the default 1000 iteration limit
        $cron = CronExpression::fromString('0 0 12 1 *');
        $cron->setMaxIterationCount(2000);
        $result = $cron->getMultipleRunDates(9, '2015-04-28 00:00:00', false, true);
        self::assertEquals([
            new DateTime('2016-01-12 00:00:00'),
            new DateTime('2017-01-12 00:00:00'),
            new DateTime('2018-01-12 00:00:00'),
            new DateTime('2019-01-12 00:00:00'),
            new DateTime('2020-01-12 00:00:00'),
            new DateTime('2021-01-12 00:00:00'),
            new DateTime('2022-01-12 00:00:00'),
            new DateTime('2023-01-12 00:00:00'),
            new DateTime('2024-01-12 00:00:00'),
        ], iterator_to_array($result));
    }

    /**
     * @covers \Cron\CronExpression
     */
    public function testCanIterateOverNextRuns(): void
    {
        $cron = CronExpression::fromString('@weekly');
        $nextRun = $cron->getNextRunDate('2008-11-09 08:00:00');
        self::assertEquals($nextRun, new DateTime('2008-11-16 00:00:00'));

        // true is cast to 1
        $nextRun = $cron->getNextRunDate('2008-11-09 00:00:00', 1, true);
        self::assertEquals($nextRun, new DateTime('2008-11-16 00:00:00'));

        // You can iterate over them
        $nextRun = $cron->getNextRunDate($cron->getNextRunDate('2008-11-09 00:00:00', 1, true), 1, true);
        self::assertEquals($nextRun, new DateTime('2008-11-23 00:00:00'));

        // You can skip more than one
        $nextRun = $cron->getNextRunDate('2008-11-09 00:00:00', 2, true);
        self::assertEquals($nextRun, new DateTime('2008-11-23 00:00:00'));
        $nextRun = $cron->getNextRunDate('2008-11-09 00:00:00', 3, true);
        self::assertEquals($nextRun, new DateTime('2008-11-30 00:00:00'));
    }

    /**
     * @covers \Cron\CronExpression::getRunDate
     */
    public function testSkipsCurrentDateByDefault(): void
    {
        $cron = CronExpression::fromString('* * * * *');
        $current = new DateTime('now');
        $next = $cron->getNextRunDate($current);
        $nextPrev = $cron->getPreviousRunDate($next);
        self::assertSame($current->format('Y-m-d H:i:00'), $nextPrev->format('Y-m-d H:i:s'));
    }

    /**
     * @covers \Cron\CronExpression::getRunDate
     * @ticket 7
     */
    public function testStripsForSeconds(): void
    {
        $cron = CronExpression::fromString('* * * * *');
        $current = new DateTime('2011-09-27 10:10:54');
        self::assertSame('2011-09-27 10:11:00', $cron->getNextRunDate($current)->format('Y-m-d H:i:s'));
    }

    /**
     * @covers \Cron\CronExpression::getRunDate
     */
    public function testFixesPhpBugInDateIntervalMonth(): void
    {
        $cron = CronExpression::fromString('0 0 27 JAN *');
        self::assertSame('2011-01-27 00:00:00', $cron->getPreviousRunDate('2011-08-22 00:00:00')->format('Y-m-d H:i:s'));
    }

    public function testIssue29(): void
    {
        $cron = CronExpression::fromString('@weekly');
        self::assertSame(
            '2013-03-10 00:00:00',
            $cron->getPreviousRunDate('2013-03-17 00:00:00')->format('Y-m-d H:i:s')
        );
    }

    /**
     * @see https://github.com/mtdowling/cron-expression/issues/20
     */
    public function testIssue20(): void
    {
        $e = CronExpression::fromString('* * * * MON#1');
        self::assertTrue($e->isDue(new DateTime('2014-04-07 00:00:00')));
        self::assertFalse($e->isDue(new DateTime('2014-04-14 00:00:00')));
        self::assertFalse($e->isDue(new DateTime('2014-04-21 00:00:00')));

        $e = CronExpression::fromString('* * * * SAT#2');
        self::assertFalse($e->isDue(new DateTime('2014-04-05 00:00:00')));
        self::assertTrue($e->isDue(new DateTime('2014-04-12 00:00:00')));
        self::assertFalse($e->isDue(new DateTime('2014-04-19 00:00:00')));

        $e = CronExpression::fromString('* * * * SUN#3');
        self::assertFalse($e->isDue(new DateTime('2014-04-13 00:00:00')));
        self::assertTrue($e->isDue(new DateTime('2014-04-20 00:00:00')));
        self::assertFalse($e->isDue(new DateTime('2014-04-27 00:00:00')));
    }

    /**
     * @covers \Cron\CronExpression::getRunDate
     */
    public function testKeepOriginalTime(): void
    {
        $now = new DateTime();
        $strNow = $now->format(DateTime::ISO8601);
        $cron = CronExpression::fromString('0 0 * * *');
        $cron->getPreviousRunDate($now);
        self::assertSame($strNow, $now->format(DateTime::ISO8601));
    }

    /**
     * @covers \Cron\CronExpression::__construct
     * @covers \Cron\CronExpression::fromString
     * @covers \Cron\CronExpression::isValidExpression
     * @covers \Cron\CronExpression::setPart
     */
    public function testValidationWorks(): void
    {
        // Invalid. Only four values
        self::assertFalse(CronExpression::isValidExpression('* * * 1'));
        // Valid
        self::assertTrue(CronExpression::isValidExpression('* * * * 1'));

        // Issue #156, 13 is an invalid month
        self::assertFalse(CronExpression::isValidExpression('* * * 13 * '));

        // Issue #155, 90 is an invalid second
        self::assertFalse(CronExpression::isValidExpression('90 * * * *'));

        // Issue #154, 24 is an invalid hour
        self::assertFalse(CronExpression::isValidExpression('0 24 1 12 0'));

        // Issue #125, this is just all sorts of wrong
        self::assertFalse(CronExpression::isValidExpression('990 14 * * mon-fri0345345'));
    }
}
