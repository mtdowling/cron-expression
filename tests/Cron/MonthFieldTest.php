<?php

namespace Cron\Tests;

use Cron\MonthField;
use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * @author Michael Dowling <mtdowling@gmail.com>
 */
class MonthFieldTest extends TestCase
{
    /**
     * @var \Cron\MonthField
     */
    protected $field;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->field = new MonthField();
    }
    /**
     * @covers \Cron\MonthField::validate
     */
    public function testValidatesField()
    {
        $this->assertTrue($this->field->validate('12'));
        $this->assertTrue($this->field->validate('*'));
        $this->assertTrue($this->field->validate('*/10,2,1-12'));
        $this->assertFalse($this->field->validate('1.fix-regexp'));
    }

    /**
     * @covers \Cron\MonthField::increment
     */
    public function testIncrementsDate()
    {
        $d = new DateTime('2011-03-15 11:15:00');
        $this->field->increment($d);
        $this->assertSame('2011-04-01 00:00:00', $d->format('Y-m-d H:i:s'));

        $d = new DateTime('2011-03-15 11:15:00');
        $this->field->increment($d, true);
        $this->assertSame('2011-02-28 23:59:00', $d->format('Y-m-d H:i:s'));
    }

    /**
     * @covers \Cron\MonthField::increment
     */
    public function testIncrementsDateWithThirtyMinuteTimezone()
    {
        $tz = date_default_timezone_get();
        date_default_timezone_set('America/St_Johns');
        $d = new DateTime('2011-03-31 11:59:59');
        $this->field->increment($d);
        $this->assertSame('2011-04-01 00:00:00', $d->format('Y-m-d H:i:s'));

        $d = new DateTime('2011-03-15 11:15:00');
        $this->field->increment($d, true);
        $this->assertSame('2011-02-28 23:59:00', $d->format('Y-m-d H:i:s'));
        date_default_timezone_set($tz);
    }


    /**
     * @covers \Cron\MonthField::increment
     */
    public function testIncrementsYearAsNeeded()
    {
        $d = new DateTime('2011-12-15 00:00:00');
        $this->field->increment($d);
        $this->assertSame('2012-01-01 00:00:00', $d->format('Y-m-d H:i:s'));
    }

    /**
     * @covers \Cron\MonthField::increment
     */
    public function testDecrementsYearAsNeeded()
    {
        $d = new DateTime('2011-01-15 00:00:00');
        $this->field->increment($d, true);
        $this->assertSame('2010-12-31 23:59:00', $d->format('Y-m-d H:i:s'));
    }
}
