<?php

namespace Cron\Tests;

use Cron\MinutesField;
use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * @author Michael Dowling <mtdowling@gmail.com>
 */
class MinutesFieldTest extends TestCase
{
    /**
     * @var \Cron\MinutesField
     */
    protected $field;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->field = new MinutesField();
    }

    /**
     * @covers \Cron\MinutesField::validate
     */
    public function testValidatesField()
    {
        $this->assertTrue($this->field->validate('1'));
        $this->assertTrue($this->field->validate('*'));
        $this->assertTrue($this->field->validate('*/3,1,1-12'));
    }

    /**
     * @covers \Cron\MinutesField::increment
     */
    public function testIncrementsDate()
    {
        $d = new DateTime('2011-03-15 11:15:00');
        $this->field->increment($d);
        $this->assertSame('2011-03-15 11:16:00', $d->format('Y-m-d H:i:s'));
        $this->field->increment($d, true);
        $this->assertSame('2011-03-15 11:15:00', $d->format('Y-m-d H:i:s'));
    }
}
