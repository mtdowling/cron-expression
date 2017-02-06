<?php

namespace Cron\Tests;

use Cron\YearField;
use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * @author Michael Dowling <mtdowling@gmail.com>
 */
class YearFieldTest extends TestCase
{
    /**
     * @var \Cron\YearField
     */
    protected $field;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->field = new YearField();
    }

    /**
     * @covers \Cron\YearField::validate
     */
    public function testValidatesField()
    {
        $this->assertTrue($this->field->validate('2011'));
        $this->assertTrue($this->field->validate('*'));
        $this->assertTrue($this->field->validate('*/10,2012,1-12'));
    }

    /**
     * @covers \Cron\YearField::increment
     */
    public function testIncrementsDate()
    {
        $d = new DateTime('2011-03-15 11:15:00');
        $this->field->increment($d);
        $this->assertSame('2012-01-01 00:00:00', $d->format('Y-m-d H:i:s'));
        $this->field->increment($d, true);
        $this->assertSame('2011-12-31 23:59:00', $d->format('Y-m-d H:i:s'));
    }
}
