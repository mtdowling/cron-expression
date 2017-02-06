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
     * @covers Cron\MinutesField::validate
     */
    public function testValidatesField()
    {
        $f = new MinutesField();
        $this->assertTrue($f->validate('1'));
        $this->assertTrue($f->validate('*'));
        $this->assertTrue($f->validate('*/3,1,1-12'));
    }

    /**
     * @covers Cron\MinutesField::increment
     */
    public function testIncrementsDate()
    {
        $d = new DateTime('2011-03-15 11:15:00');
        $f = new MinutesField();
        $f->increment($d);
        $this->assertSame('2011-03-15 11:16:00', $d->format('Y-m-d H:i:s'));
        $f->increment($d, true);
        $this->assertSame('2011-03-15 11:15:00', $d->format('Y-m-d H:i:s'));
    }
}
