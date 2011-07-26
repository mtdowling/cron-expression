<?php

namespace Cron\Tests;

use Cron\CronExpression;

/**
 * @author Michael Dowling <mtdowling@gmail.com>
 */
class CronExpressionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Cron\CronExpression::factory
     */
    public function testFactoryRecognizesTemplates()
    {
        $this->assertEquals('0 0 1 1 *', CronExpression::factory('@annually')->getExpression());
        $this->assertEquals('0 0 1 1 *', CronExpression::factory('@yearly')->getExpression());
        $this->assertEquals('0 0 * * 0', CronExpression::factory('@weekly')->getExpression());
    }

    /**
     * @covers Cron\CronExpression::__construct
     * @covers Cron\CronExpression::getExpression
     */
    public function testParsesCronSchedule()
    {
        // '2010-09-10 12:00:00'
        $cron = CronExpression::factory('1 2-4 * 4,5,6 */3');
        $this->assertEquals('1', $cron->getExpression(CronExpression::MINUTE));
        $this->assertEquals('2-4', $cron->getExpression(CronExpression::HOUR));
        $this->assertEquals('*', $cron->getExpression(CronExpression::DAY));
        $this->assertEquals('4,5,6', $cron->getExpression(CronExpression::MONTH));
        $this->assertEquals('*/3', $cron->getExpression(CronExpression::WEEKDAY));
        $this->assertEquals('1 2-4 * 4,5,6 */3', $cron->getExpression());
        $this->assertNull($cron->getExpression('foo'));

        try {
            $cron = CronExpression::factory('A 1 2 3 4');
            $this->fail('Validation exception not thrown');
        } catch (\InvalidArgumentException $e) {
        }
    }

    /**
     * @covers Cron\CronExpression::__construct
     * @expectedException InvalidArgumentException
     */
    public function testInvalidCronsWillFail()
    {
        // Only four values
        $cron = CronExpression::factory('* * * 1');
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
            array('* 5,21-22 * * *', '2015-08-10 21:50:00', '2015-08-10 21:50:00', true),
            array('7-9 * */9 * *', '2015-08-10 22:02:33', '2015-08-18 00:07:00', false),
            // Minutes 12-19, every 3 hours, every 5 days, in June, on Sunday
            array('12-19 */3 */5 6 7', '2015-08-10 22:05:51', '2016-06-05 00:12:00', false),
            // 15th minute, of the second hour, every 15 days, in January, every Friday
            array('15 2 */15 1 */5', '2015-08-10 22:10:19', '2016-01-15 02:15:00', false),
            // 15th minute, of the second hour, every 15 days, in January, Tuesday-Friday
            array('15 2 */15 1 2-5', '2015-08-10 22:10:19', '2016-01-15 02:15:00', false),
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
            // Test lists of values and increments of ranges (Abhoryo)
            array('0 0 * * 2-7', strtotime('2011-06-20 23:09:00'), '2011-06-21 00:00:00', false),
            array('0 0 * * 0,2-6', strtotime('2011-06-20 23:09:00'), '2011-06-21 00:00:00', false),
            array('0 0 * * 2-7', strtotime('2011-06-18 23:09:00'), '2011-06-19 00:00:00', false),
            array('0 0 * * 4-7', strtotime('2011-07-19 00:00:00'), '2011-07-21 00:00:00', false),
            // Test Day of the Week and the Day of the Month (issue #1)
            array('0 0 1 1 0', strtotime('2011-06-15 23:09:00'), '2012-01-01 00:00:00', false),
            array('0 0 1 JAN 0', strtotime('2011-06-15 23:09:00'), '2012-01-01 00:00:00', false),
            array('0 0 1 * 0', strtotime('2011-06-15 23:09:00'), '2012-01-01 00:00:00', false),
            array('0 0 L * *', strtotime('2011-07-15 00:00:00'), '2011-07-31 00:00:00', false),
            // Test the W day of the week modifier for day of the month field
            array('0 0 2W * *', strtotime('2011-07-01 00:00:00'), '2011-07-01 00:00:00', true),
            array('0 0 1W * *', strtotime('2011-05-01 00:00:00'), '2011-05-02 00:00:00', false),
            array('0 0 1W * *', strtotime('2011-07-01 00:00:00'), '2011-07-01 00:00:00', true),
            array('0 0 3W * *', strtotime('2011-07-01 00:00:00'), '2011-07-04 00:00:00', false),
            array('0 0 16W * *', strtotime('2011-07-01 00:00:00'), '2011-07-15 00:00:00', false),
            array('0 0 28W * *', strtotime('2011-07-01 00:00:00'), '2011-07-28 00:00:00', false),
            array('0 0 30W * *', strtotime('2011-07-01 00:00:00'), '2011-07-29 00:00:00', false),
            array('0 0 31W * *', strtotime('2011-07-01 00:00:00'), '2011-07-29 00:00:00', false),
            // Test the year field
            array('* * * * * 2012', strtotime('2011-05-01 00:00:00'), '2012-01-01 00:00:00', false),
        );
    }

    /**
     * @covers Cron\CronExpression::isDue
     * @covers Cron\CronExpression::getNextRunDate
     * @covers Cron\DayOfMonthField
     * @covers Cron\DayOfWeekField
     * @covers Cron\MinutesField
     * @covers Cron\HoursField
     * @covers Cron\MonthField
     * @covers Cron\YearField
     * @dataProvider scheduleProvider
     */
    public function testDeterminesIfCronIsDue($schedule, $relativeTime, $nextRun, $isDue)
    {
        $cron = CronExpression::factory($schedule);
        if (is_string($relativeTime)) {
            $relativeTime = new \DateTime($relativeTime);
        } else if (is_int($relativeTime)) {
            $relativeTime = date('Y-m-d H:i:s', $relativeTime);
        }
        $this->assertEquals($isDue, $cron->isDue($relativeTime));
        $this->assertEquals(new \DateTime($nextRun), $cron->getNextRunDate($relativeTime));
    }

    /**
     * @covers Cron\CronExpression::isDue
     */
    public function testIsDueHandlesDifferentDates()
    {
        $cron = CronExpression::factory('* * * * *');
        $this->assertTrue($cron->isDue());
        $this->assertTrue($cron->isDue('now'));
        $this->assertTrue($cron->isDue(new \DateTime('now')));
        $this->assertTrue($cron->isDue(date('Y-m-d H:i')));
    }
}