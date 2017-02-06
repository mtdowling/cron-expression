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
     * @var \Cron\DayOfMonthField
     */
    protected $field;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->field = new DayOfMonthField();
    }

    /**
     * @covers \Cron\DayOfMonthField::validate
     */
    public function testValidatesField()
    {
        $this->assertTrue($this->field->validate('1'));
        $this->assertTrue($this->field->validate('*'));
        $this->assertTrue($this->field->validate('5W,L'));
        $this->assertFalse($this->field->validate('1.'));
    }

    /**
     * @covers \Cron\DayOfMonthField::isSatisfiedBy
     */
    public function testChecksIfSatisfied()
    {
        $this->assertTrue($this->field->isSatisfiedBy(new DateTime(), '?'));
    }

    /**
     * @covers \Cron\DayOfMonthField::increment
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
     * Day of the month cannot accept a 0 value, it must be between 1 and 31
     * See Github issue #120
     *
     * @since 2017-01-22
     */
    public function testDoesNotAccept0Date()
    {
        $this->assertFalse($this->field->validate(0));
    }
}
