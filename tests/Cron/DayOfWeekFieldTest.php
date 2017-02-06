<?php

namespace Cron\Tests;

use Cron\DayOfWeekField;
use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * @author Michael Dowling <mtdowling@gmail.com>
 */
class DayOfWeekFieldTest extends TestCase
{
    /**
     * @var \Cron\DayOfWeekField
     */
    protected $field;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->field = new DayOfWeekField();
    }

    /**
     * @covers \Cron\DayOfWeekField::validate
     */
    public function testValidatesField()
    {
        $this->assertTrue($this->field->validate('1'));
        $this->assertTrue($this->field->validate('*'));
        $this->assertTrue($this->field->validate('*/3,1,1-12'));
        $this->assertTrue($this->field->validate('SUN-2'));
        $this->assertFalse($this->field->validate('1.'));
    }

    /**
     * @covers \Cron\DayOfWeekField::isSatisfiedBy
     */
    public function testChecksIfSatisfied()
    {
        $this->assertTrue($this->field->isSatisfiedBy(new DateTime(), '?'));
    }

    /**
     * @covers \Cron\DayOfWeekField::increment
     */
    public function testIncrementsDate()
    {
        $d = new DateTime('2011-03-15 11:15:00');
        $this->field->increment($d);
        $this->assertSame('2011-03-16 00:00:00', $d->format('Y-m-d H:i:s'));

        $d = new DateTime('2011-03-15 11:15:00');
        $this->field->increment($d, true);
        $this->assertSame('2011-03-14 23:59:00', $d->format('Y-m-d H:i:s'));
    }

    /**
     * @covers \Cron\DayOfWeekField::isSatisfiedBy
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Weekday must be a value between 0 and 7. 12 given
     */
    public function testValidatesHashValueWeekday()
    {
        $this->assertTrue($this->field->isSatisfiedBy(new DateTime(), '12#1'));
    }

    /**
     * @covers \Cron\DayOfWeekField::isSatisfiedBy
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage There are never more than 5 of a given weekday in a month
     */
    public function testValidatesHashValueNth()
    {
        $this->assertTrue($this->field->isSatisfiedBy(new DateTime(), '3#6'));
    }

    /**
     * @covers \Cron\DayOfWeekField::validate
     */
    public function testValidateWeekendHash()
    {
        $this->assertTrue($this->field->validate('MON#1'));
        $this->assertTrue($this->field->validate('TUE#2'));
        $this->assertTrue($this->field->validate('WED#3'));
        $this->assertTrue($this->field->validate('THU#4'));
        $this->assertTrue($this->field->validate('FRI#5'));
        $this->assertTrue($this->field->validate('SAT#1'));
        $this->assertTrue($this->field->validate('SUN#3'));
        $this->assertTrue($this->field->validate('MON#1,MON#3'));
    }

    /**
     * @covers \Cron\DayOfWeekField::isSatisfiedBy
     */
    public function testHandlesZeroAndSevenDayOfTheWeekValues()
    {
        $this->assertTrue($this->field->isSatisfiedBy(new DateTime('2011-09-04 00:00:00'), '0-2'));
        $this->assertTrue($this->field->isSatisfiedBy(new DateTime('2011-09-04 00:00:00'), '6-0'));

        $this->assertTrue($this->field->isSatisfiedBy(new DateTime('2014-04-20 00:00:00'), 'SUN'));
        $this->assertTrue($this->field->isSatisfiedBy(new DateTime('2014-04-20 00:00:00'), 'SUN#3'));
        $this->assertTrue($this->field->isSatisfiedBy(new DateTime('2014-04-20 00:00:00'), '0#3'));
        $this->assertTrue($this->field->isSatisfiedBy(new DateTime('2014-04-20 00:00:00'), '7#3'));
    }

    /**
     * @see https://github.com/mtdowling/cron-expression/issues/47
     */
    public function testIssue47() {
        
        $this->assertFalse($this->field->validate('mon,'));
        $this->assertFalse($this->field->validate('mon-'));
        $this->assertFalse($this->field->validate('*/2,'));
        $this->assertFalse($this->field->validate('-mon'));
        $this->assertFalse($this->field->validate(',1'));
        $this->assertFalse($this->field->validate('*-'));
        $this->assertFalse($this->field->validate(',-'));
    }
}
