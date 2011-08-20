<?php

namespace Cron\Tests;

use Cron\MonthField;
use DateTime;

/**
 * @author Michael Dowling <mtdowling@gmail.com>
 */
class MonthFieldTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Cron\MonthField::validate
     */
    public function testValdatesField()
    {
        $f = new MonthField();
        $this->assertTrue($f->validate('12'));
        $this->assertTrue($f->validate('*'));
        $this->assertTrue($f->validate('*/10,2,1-12'));
    }

    /**
     * @covers Cron\MonthField::increment
     */
    public function testIncrementsDate()
    {
        $d = new DateTime('2011-03-15 11:15:00');
        $f = new MonthField();
        $f->increment($d);
        $this->assertEquals('2011-04-01 00:00:00', $d->format('Y-m-d H:i:s'));

        $d = new DateTime('2011-03-15 11:15:00');
        $f->increment($d, true);
        $this->assertEquals('2011-02-28 23:59:00', $d->format('Y-m-d H:i:s'));
    }
}