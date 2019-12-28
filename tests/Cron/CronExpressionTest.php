<?php

namespace Cron\Tests;

use Cron\CronExpression;
use DateTime;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @author Michael Dowling <mtdowling@gmail.com>
 */
class CronExpressionTest extends TestCase
{
    /**
     * @covers \Cron\CronExpression::factory
     */
    public function testFactoryRecognizesTemplates()
    {
        $this->assertSame('0 0 1 1 *', CronExpression::factory('@annually')->getExpression());
        $this->assertSame('0 0 1 1 *', CronExpression::factory('@yearly')->getExpression());
        $this->assertSame('0 0 * * 0', CronExpression::factory('@weekly')->getExpression());
    }

    /**
     * @covers \Cron\CronExpression::__construct
     * @covers \Cron\CronExpression::getExpression
     * @covers \Cron\CronExpression::__toString
     */
    public function testParsesCronSchedule()
    {
        // '2010-09-10 12:00:00'
        $cron = CronExpression::factory('1 2-4 * 4,5,6 */3');
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
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid CRON field value A at position 0
     */
    public function testParsesCronScheduleThrowsAnException()
    {
        CronExpression::factory('A 1 2 3 4');
    }

    /**
     * @covers \Cron\CronExpression::__construct
     * @covers \Cron\CronExpression::getExpression
     * @dataProvider scheduleWithDifferentSeparatorsProvider
     */
    public function testParsesCronScheduleWithAnySpaceCharsAsSeparators($schedule, array $expected)
    {
        $cron = CronExpression::factory($schedule);
        $this->assertSame($expected[0], $cron->getExpression(CronExpression::MINUTE));
        $this->assertSame($expected[1], $cron->getExpression(CronExpression::HOUR));
        $this->assertSame($expected[2], $cron->getExpression(CronExpression::DAY));
        $this->assertSame($expected[3], $cron->getExpression(CronExpression::MONTH));
        $this->assertSame($expected[4], $cron->getExpression(CronExpression::WEEKDAY));
    }

    /**
     * Data provider for testParsesCronScheduleWithAnySpaceCharsAsSeparators
     *
     * @return array
     */
    public static function scheduleWithDifferentSeparatorsProvider()
    {
        return array(
            array("*\t*\t*\t*\t*\t", array('*', '*', '*', '*', '*', '*')),
            array("*  *  *  *  *  ", array('*', '*', '*', '*', '*', '*')),
            array("* \t * \t * \t * \t * \t", array('*', '*', '*', '*', '*', '*')),
            array("*\t \t*\t \t*\t \t*\t \t*\t \t", array('*', '*', '*', '*', '*', '*')),
        );
    }

    /**
     * @covers \Cron\CronExpression::__construct
     * @covers \Cron\CronExpression::setExpression
     * @covers \Cron\CronExpression::setPart
     * @expectedException InvalidArgumentException
     */
    public function testInvalidCronsWillFail()
    {
        // Only four values
        $cron = CronExpression::factory('* * * 1');
    }

    /**
     * @covers \Cron\CronExpression::setPart
     * @expectedException InvalidArgumentException
     */
    public function testInvalidPartsWillFail()
    {
        // Only four values
        $cron = CronExpression::factory('* * * * *');
        $cron->setPart(1, 'abc');
    }

    /**
     * Data provider for cron schedule
     *
     * @return array
     */
    public function scheduleProvider()
    {
        return array(
            array('*/2 */2 * * *', '2015-08-10 21:47:27', '2015-08-10 22:00:00', false),
            array('* * * * *', '2015-08-10 21:50:37', '2015-08-10 21:50:00', true),
            array('* 20,21,22 * * *', '2015-08-10 21:50:00', '2015-08-10 21:50:00', true),
            // Handles CSV values
            array('* 20,22 * * *', '2015-08-10 21:50:00', '2015-08-10 22:00:00', false),
            // CSV values can be complex
            array('7-9 * */9 * *', '2015-08-10 22:02:33', '2015-08-10 22:07:00', false),
            // 15th minute, of the second hour, every 15 days, in January, every Friday
            array('1 * * * 7', '2015-08-10 21:47:27', '2015-08-16 00:01:00', false),
            // Test with exact times
            array('47 21 * * *', strtotime('2015-08-10 21:47:30'), '2015-08-10 21:47:00', true),
            // Test Day of the week (issue #1)
            // According cron implementation, 0|7 = sunday, 1 => monday, etc
            array('* * * * 0', strtotime('2011-06-15 23:09:00'), '2011-06-19 00:00:00', false),
            array('* * * * 7', strtotime('2011-06-15 23:09:00'), '2011-06-19 00:00:00', false),
            array('* * * * 1', strtotime('2011-06-15 23:09:00'), '2011-06-20 00:00:00', false),
            // Should return the sunday date as 7 equals 0
            array('0 0 * * MON,SUN', strtotime('2011-06-15 23:09:00'), '2011-06-19 00:00:00', false),
            array('0 0 * * 1,7', strtotime('2011-06-15 23:09:00'), '2011-06-19 00:00:00', false),
            array('0 0 * * 0-4', strtotime('2011-06-15 23:09:00'), '2011-06-16 00:00:00', false),
            array('0 0 * * 7-4', strtotime('2011-06-15 23:09:00'), '2011-06-16 00:00:00', false),
            array('0 0 * * 4-7', strtotime('2011-06-15 23:09:00'), '2011-06-16 00:00:00', false),
            array('0 0 * * 7-3', strtotime('2011-06-15 23:09:00'), '2011-06-19 00:00:00', false),
            array('0 0 * * 3-7', strtotime('2011-06-15 23:09:00'), '2011-06-16 00:00:00', false),
            array('0 0 * * 3-7', strtotime('2011-06-18 23:09:00'), '2011-06-19 00:00:00', false),
            // Test lists of values and ranges (Abhoryo)
            array('0 0 * * 2-7', strtotime('2011-06-20 23:09:00'), '2011-06-21 00:00:00', false),
            array('0 0 * * 2-7', strtotime('2011-06-18 23:09:00'), '2011-06-19 00:00:00', false),
            array('0 0 * * 4-7', strtotime('2011-07-19 00:00:00'), '2011-07-21 00:00:00', false),
            // Test increments of ranges
            array('0-12/4 * * * *', strtotime('2011-06-20 12:04:00'), '2011-06-20 12:04:00', true),
            array('4-59/2 * * * *', strtotime('2011-06-20 12:04:00'), '2011-06-20 12:04:00', true),
            array('4-59/2 * * * *', strtotime('2011-06-20 12:06:00'), '2011-06-20 12:06:00', true),
            array('4-59/3 * * * *', strtotime('2011-06-20 12:06:00'), '2011-06-20 12:07:00', false),
            // Test Day of the Week and the Day of the Month (issue #1)
            array('0 0 1 1 0', strtotime('2011-06-15 23:09:00'), '2012-01-01 00:00:00', false),
            array('0 0 1 JAN 0', strtotime('2011-06-15 23:09:00'), '2012-01-01 00:00:00', false),
            array('0 0 1 * 0', strtotime('2011-06-15 23:09:00'), '2012-01-01 00:00:00', false),
            // Test the W day of the week modifier for day of the month field
            array('0 0 2W * *', strtotime('2011-07-01 00:00:00'), '2011-07-01 00:00:00', true),
            array('0 0 1W * *', strtotime('2011-05-01 00:00:00'), '2011-05-02 00:00:00', false),
            array('0 0 1W * *', strtotime('2011-07-01 00:00:00'), '2011-07-01 00:00:00', true),
            array('0 0 3W * *', strtotime('2011-07-01 00:00:00'), '2011-07-04 00:00:00', false),
            array('0 0 16W * *', strtotime('2011-07-01 00:00:00'), '2011-07-15 00:00:00', false),
            array('0 0 28W * *', strtotime('2011-07-01 00:00:00'), '2011-07-28 00:00:00', false),
            array('0 0 30W * *', strtotime('2011-07-01 00:00:00'), '2011-07-29 00:00:00', false),
            array('0 0 31W * *', strtotime('2011-07-01 00:00:00'), '2011-07-29 00:00:00', false),
            // Test the last weekday of a month
            array('* * * * 5L', strtotime('2011-07-01 00:00:00'), '2011-07-29 00:00:00', false),
            array('* * * * 6L', strtotime('2011-07-01 00:00:00'), '2011-07-30 00:00:00', false),
            array('* * * * 7L', strtotime('2011-07-01 00:00:00'), '2011-07-31 00:00:00', false),
            array('* * * * 1L', strtotime('2011-07-24 00:00:00'), '2011-07-25 00:00:00', false),
            array('* * * 1 5L', strtotime('2011-12-25 00:00:00'), '2012-01-27 00:00:00', false),
            // Test the hash symbol for the nth weekday of a given month
            array('* * * * 5#2', strtotime('2011-07-01 00:00:00'), '2011-07-08 00:00:00', false),
            array('* * * * 5#1', strtotime('2011-07-01 00:00:00'), '2011-07-01 00:00:00', true),
            array('* * * * 3#4', strtotime('2011-07-01 00:00:00'), '2011-07-27 00:00:00', false),
        );
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
    public function testDeterminesIfCronIsDue($schedule, $relativeTime, $nextRun, $isDue)
    {
        $relativeTimeString = is_int($relativeTime) ? date('Y-m-d H:i:s', $relativeTime) : $relativeTime;

        // Test next run date
        $cron = CronExpression::factory($schedule);
        if (is_string($relativeTime)) {
            $relativeTime = new DateTime($relativeTime);
        } elseif (is_int($relativeTime)) {
            $relativeTime = date('Y-m-d H:i:s', $relativeTime);
        }
        $this->assertSame($isDue, $cron->isDue($relativeTime));
        $next = $cron->getNextRunDate($relativeTime, 0, true);
        $this->assertEquals(new DateTime($nextRun), $next);
    }

    /**
     * @covers \Cron\CronExpression::isDue
     */
    public function testIsDueHandlesDifferentDates()
    {
        $cron = CronExpression::factory('* * * * *');
        $this->assertTrue($cron->isDue());
        $this->assertTrue($cron->isDue('now'));
        $this->assertTrue($cron->isDue(new DateTime('now')));
        $this->assertTrue($cron->isDue(date('Y-m-d H:i')));
    }

    /**
     * @covers \Cron\CronExpression::isDue
     */
    public function testIsDueHandlesDifferentTimezones()
    {
        $cron = CronExpression::factory('0 15 * * 3'); //Wednesday at 15:00
        $date = '2014-01-01 15:00'; //Wednesday
        $utc = new DateTimeZone('UTC');
        $amsterdam =  new DateTimeZone('Europe/Amsterdam');
        $tokyo = new DateTimeZone('Asia/Tokyo');
        $defaultTimeZone = date_default_timezone_get();

        date_default_timezone_set('UTC');
        $this->assertTrue($cron->isDue(new DateTime($date, $utc)));
        $this->assertFalse($cron->isDue(new DateTime($date, $amsterdam)));
        $this->assertFalse($cron->isDue(new DateTime($date, $tokyo)));

        date_default_timezone_set('Europe/Amsterdam');
        $this->assertFalse($cron->isDue(new DateTime($date, $utc)));
        $this->assertTrue($cron->isDue(new DateTime($date, $amsterdam)));
        $this->assertFalse($cron->isDue(new DateTime($date, $tokyo)));

        date_default_timezone_set('Asia/Tokyo');
        $this->assertFalse($cron->isDue(new DateTime($date, $utc)));
        $this->assertFalse($cron->isDue(new DateTime($date, $amsterdam)));
        $this->assertTrue($cron->isDue(new DateTime($date, $tokyo)));

        //Set default timezone again
        date_default_timezone_set($defaultTimeZone);
    }

   /**
     * @covers Cron\CronExpression::isDue
     */
    public function testIsDueHandlesDifferentTimezonesAsArgument()
    {
        $cron      = CronExpression::factory('0 15 * * 3'); //Wednesday at 15:00
        $date      = '2014-01-01 15:00'; //Wednesday
        $utc       = new \DateTimeZone('UTC');
        $amsterdam = new \DateTimeZone('Europe/Amsterdam');
        $tokyo     = new \DateTimeZone('Asia/Tokyo');
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
     * @covers \Cron\CronExpression::getPreviousRunDate
     */
    public function testCanGetPreviousRunDates()
    {
        $cron = CronExpression::factory('* * * * *');
        $next = $cron->getNextRunDate('now');
        $two = $cron->getNextRunDate('now', 1);
        $this->assertEquals($next, $cron->getPreviousRunDate($two));

        $cron = CronExpression::factory('* */2 * * *');
        $next = $cron->getNextRunDate('now');
        $two = $cron->getNextRunDate('now', 1);
        $this->assertEquals($next, $cron->getPreviousRunDate($two));

        $cron = CronExpression::factory('* * * */2 *');
        $next = $cron->getNextRunDate('now');
        $two = $cron->getNextRunDate('now', 1);
        $this->assertEquals($next, $cron->getPreviousRunDate($two));
    }

    /**
     * @covers \Cron\CronExpression::getMultipleRunDates
     */
    public function testProvidesMultipleRunDates()
    {
        $cron = CronExpression::factory('*/2 * * * *');
        $this->assertEquals(array(
            new DateTime('2008-11-09 00:00:00'),
            new DateTime('2008-11-09 00:02:00'),
            new DateTime('2008-11-09 00:04:00'),
            new DateTime('2008-11-09 00:06:00')
        ), $cron->getMultipleRunDates(4, '2008-11-09 00:00:00', false, true));
    }

    /**
     * @covers \Cron\CronExpression::getMultipleRunDates
     * @covers \Cron\CronExpression::setMaxIterationCount
     */
    public function testProvidesMultipleRunDatesForTheFarFuture() {
        // Fails with the default 1000 iteration limit
        $cron = CronExpression::factory('0 0 12 1 *');
        $cron->setMaxIterationCount(2000);
        $this->assertEquals(array(
            new DateTime('2016-01-12 00:00:00'),
            new DateTime('2017-01-12 00:00:00'),
            new DateTime('2018-01-12 00:00:00'),
            new DateTime('2019-01-12 00:00:00'),
            new DateTime('2020-01-12 00:00:00'),
            new DateTime('2021-01-12 00:00:00'),
            new DateTime('2022-01-12 00:00:00'),
            new DateTime('2023-01-12 00:00:00'),
            new DateTime('2024-01-12 00:00:00'),
        ), $cron->getMultipleRunDates(9, '2015-04-28 00:00:00', false, true));
    }

    /**
     * @covers \Cron\CronExpression
     */
    public function testCanIterateOverNextRuns()
    {
        $cron = CronExpression::factory('@weekly');
        $nextRun = $cron->getNextRunDate("2008-11-09 08:00:00");
        $this->assertEquals($nextRun, new DateTime("2008-11-16 00:00:00"));

        // true is cast to 1
        $nextRun = $cron->getNextRunDate("2008-11-09 00:00:00", true, true);
        $this->assertEquals($nextRun, new DateTime("2008-11-16 00:00:00"));

        // You can iterate over them
        $nextRun = $cron->getNextRunDate($cron->getNextRunDate("2008-11-09 00:00:00", 1, true), 1, true);
        $this->assertEquals($nextRun, new DateTime("2008-11-23 00:00:00"));

        // You can skip more than one
        $nextRun = $cron->getNextRunDate("2008-11-09 00:00:00", 2, true);
        $this->assertEquals($nextRun, new DateTime("2008-11-23 00:00:00"));
        $nextRun = $cron->getNextRunDate("2008-11-09 00:00:00", 3, true);
        $this->assertEquals($nextRun, new DateTime("2008-11-30 00:00:00"));
    }

    /**
     * @covers \Cron\CronExpression::getRunDate
     */
    public function testSkipsCurrentDateByDefault()
    {
        $cron = CronExpression::factory('* * * * *');
        $current = new DateTime('now');
        $next = $cron->getNextRunDate($current);
        $nextPrev = $cron->getPreviousRunDate($next);
        $this->assertSame($current->format('Y-m-d H:i:00'), $nextPrev->format('Y-m-d H:i:s'));
    }

    /**
     * @covers \Cron\CronExpression::getRunDate
     * @ticket 7
     */
    public function testStripsForSeconds()
    {
        $cron = CronExpression::factory('* * * * *');
        $current = new DateTime('2011-09-27 10:10:54');
        $this->assertSame('2011-09-27 10:11:00', $cron->getNextRunDate($current)->format('Y-m-d H:i:s'));
    }

    /**
     * @covers \Cron\CronExpression::getRunDate
     */
    public function testFixesPhpBugInDateIntervalMonth()
    {
        $cron = CronExpression::factory('0 0 27 JAN *');
        $this->assertSame('2011-01-27 00:00:00', $cron->getPreviousRunDate('2011-08-22 00:00:00')->format('Y-m-d H:i:s'));
    }

    public function testIssue29()
    {
        $cron = CronExpression::factory('@weekly');
        $this->assertSame(
            '2013-03-10 00:00:00',
            $cron->getPreviousRunDate('2013-03-17 00:00:00')->format('Y-m-d H:i:s')
        );
    }

    /**
     * @see https://github.com/mtdowling/cron-expression/issues/20
     */
    public function testIssue20() {
        $e = CronExpression::factory('* * * * MON#1');
        $this->assertTrue($e->isDue(new DateTime('2014-04-07 00:00:00')));
        $this->assertFalse($e->isDue(new DateTime('2014-04-14 00:00:00')));
        $this->assertFalse($e->isDue(new DateTime('2014-04-21 00:00:00')));

        $e = CronExpression::factory('* * * * SAT#2');
        $this->assertFalse($e->isDue(new DateTime('2014-04-05 00:00:00')));
        $this->assertTrue($e->isDue(new DateTime('2014-04-12 00:00:00')));
        $this->assertFalse($e->isDue(new DateTime('2014-04-19 00:00:00')));

        $e = CronExpression::factory('* * * * SUN#3');
        $this->assertFalse($e->isDue(new DateTime('2014-04-13 00:00:00')));
        $this->assertTrue($e->isDue(new DateTime('2014-04-20 00:00:00')));
        $this->assertFalse($e->isDue(new DateTime('2014-04-27 00:00:00')));
    }

    /**
     * @covers \Cron\CronExpression::getRunDate
     */
    public function testKeepOriginalTime()
    {
        $now = new \DateTime;
        $strNow = $now->format(DateTime::ISO8601);
        $cron = CronExpression::factory('0 0 * * *');
        $cron->getPreviousRunDate($now);
        $this->assertSame($strNow, $now->format(DateTime::ISO8601));
    }

    /**
     * @covers \Cron\CronExpression::__construct
     * @covers \Cron\CronExpression::factory
     * @covers \Cron\CronExpression::isValidExpression
     * @covers \Cron\CronExpression::setExpression
     * @covers \Cron\CronExpression::setPart
     */
    public function testValidationWorks()
    {
        // Invalid. Only four values
        $this->assertFalse(CronExpression::isValidExpression('* * * 1'));
        // Valid
        $this->assertTrue(CronExpression::isValidExpression('* * * * 1'));

        // Issue #156, 13 is an invalid month
        $this->assertFalse(CronExpression::isValidExpression("* * * 13 * "));

        // Issue #155, 90 is an invalid second
        $this->assertFalse(CronExpression::isValidExpression('90 * * * *'));

        // Issue #154, 24 is an invalid hour
        $this->assertFalse(CronExpression::isValidExpression("0 24 1 12 0"));

        // Issue #125, this is just all sorts of wrong
        $this->assertFalse(CronExpression::isValidExpression('990 14 * * mon-fri0345345'));
    }
}
