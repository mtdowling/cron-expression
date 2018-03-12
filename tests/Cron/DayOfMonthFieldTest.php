<?php

namespace Cron\Tests;

use Cron\DayOfMonthField;
use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * @author Michael Dowling <mtdowling@gmail.com>
 */
class DayOfMonthFieldTest extends TestCase
{
    /**
     * @covers \Cron\DayOfMonthField::validate
     */
    public function testValidatesField()
    {
        $f = new DayOfMonthField();
        $this->assertTrue($f->validate('1'));
        $this->assertTrue($f->validate('*'));
        $this->assertTrue($f->validate('L'));
        $this->assertTrue($f->validate('5W'));
        $this->assertFalse($f->validate('5W,L'));
        $this->assertFalse($f->validate('1.'));
    }

    /**
     * @covers \Cron\DayOfMonthField::isSatisfiedBy
     */
    public function testChecksIfSatisfied()
    {
        $f = new DayOfMonthField();
        $this->assertTrue($f->isSatisfiedBy(new DateTime(), '?'));
    }

    /**
     * @covers \Cron\DayOfMonthField::isInIncrementsOfRanges
     * @expectedException \OutOfRangeException
     * @expectedExceptionMessage Invalid range start requested
     */
    public function testDateWithInvalidStartShouldThrowOutOfRangeException()
    {
        $f = new DayOfMonthField();
        $f->isSatisfiedBy(new DateTime(), '2018/03/02');
    }

    /**
     * @covers \Cron\DayOfMonthField::isInIncrementsOfRanges
     * @expectedException \OutOfRangeException
     * @expectedExceptionMessage Invalid range end requested
     */
    public function testDateWithInvalidEndShouldThrowOutOfRangeException()
    {
        $f = new DayOfMonthField();
        $f->isSatisfiedBy(new DateTime(), '7-2018/04:05:00');
    }

    /**
     * @covers \Cron\AbstractField::getRangeForExpression
     */
    public function testGetRangeForExpression()
    {
        $f = new DayOfMonthField();
        $this->assertSame([], $f->getRangeForExpression('2018-03-13 04:05:00', 5));
        $this->assertSame([], $f->getRangeForExpression('2018/03/13 04:05:00', 5));
        $this->assertSame(['3', 4, 5], $f->getRangeForExpression('3-5-15', 15));
    }

    /**
     * @covers \Cron\AbstractField::validate
     */
    public function testValidateShouldReturnTrue()
    {
        $f = new DayOfMonthField();
        $this->assertTrue($f->validate('2,12'));
    }

    /**
     * @covers \Cron\DayOfMonthField::isSatisfiedBy
     */
    public function testIsSatipsfiedByOnLValue()
    {
        $f = new DayOfMonthField();
        $this->assertFalse($f->isSatisfiedBy(new DateTime, 'L'));
    }

    /**
     * @covers \Cron\DayOfMonthField::increment
     */
    public function testIncrementsDate()
    {
        $d = new DateTime('2011-03-15 11:15:00');
        $f = new DayOfMonthField();
        $f->increment($d);
        $this->assertSame('2011-03-16 00:00:00', $d->format('Y-m-d H:i:s'));

        $d = new DateTime('2011-03-15 11:15:00');
        $f->increment($d, true);
        $this->assertSame('2011-03-14 23:59:00', $d->format('Y-m-d H:i:s'));
    }

    /**
     * Day of the month cannot accept a 0 value, it must be between 1 and 31
     * See Github issue #120
     *
     * @since 2017-01-22
     */
    public function testDoesNotAccept0Date()
    {
        $f = new DayOfMonthField();
        $this->assertFalse($f->validate(0));
    }
}
