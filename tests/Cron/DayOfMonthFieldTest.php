<?php

namespace Cron\Tests;

use Cron\DayOfMonthField;

use DateTime;

/**
 * @author Michael Dowling <mtdowling@gmail.com>
 */
class DayOfMonthFieldTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Cron\DayOfMonthField::validate
     */
    public function testValdatesField()
    {
        $f = new DayOfMonthField();
        $this->assertTrue($f->validate('1'));
        $this->assertTrue($f->validate('*'));
        $this->assertTrue($f->validate('*/3,1,1-12'));
        $this->assertTrue($f->validate('5W, L'));
    }

    /**
     * @covers Cron\DayOfMonthField::isSatisfiedBy
     */
    public function testChecksIfSatisfied()
    {
        $f = new DayOfMonthField();
        $this->assertTrue($f->isSatisfiedBy(new DateTime(), '?'));
    }

    /**
     * @covers Cron\DayOfMonthField::increment
     */
    public function testIncrementsDate()
    {
        $d = new DateTime('2011-03-15 11:15:00');
        $f = new DayOfMonthField();
        $f->increment($d);
        $this->assertEquals('2011-03-16 00:00:00', $d->format('Y-m-d H:i:s'));

        $d = new DateTime('2011-03-15 11:15:00');
        $f->increment($d, true);
        $this->assertEquals('2011-03-14 23:59:00', $d->format('Y-m-d H:i:s'));
    }

    public function dayOfMonthProvider()
    {
        return array(
            array(new DateTime('2011-01-20 00:00:00'), 31),
            array(new DateTime('2011-02-20 00:00:00'), 28),
            array(new DateTime('2012-02-20 00:00:00'), 29), // Leap year
            array(new DateTime('2011-03-20 00:00:00'), 31),
            array(new DateTime('2011-04-20 00:00:00'), 30),
            array(new DateTime('2011-05-20 00:00:00'), 31),
            array(new DateTime('2011-06-20 00:00:00'), 30),
            array(new DateTime('2011-07-20 00:00:00'), 31),
            array(new DateTime('2011-08-20 00:00:00'), 31),
            array(new DateTime('2011-09-20 00:00:00'), 30),
            array(new DateTime('2011-10-20 00:00:00'), 31),
            array(new DateTime('2011-11-20 00:00:00'), 30),
            array(new DateTime('2011-12-20 00:00:00'), 31),
        );
    }

    /**
     * @covers Cron\DayOfMonthField::getLastDayOfMonth
     * @dataProvider dayOfMonthProvider
     */
    public function testGetsTheLastDayOfMonth(DateTime $date, $days)
    {
        $this->assertEquals($days, DayOfMonthField::getLastDayOfMonth($date));
    }
}