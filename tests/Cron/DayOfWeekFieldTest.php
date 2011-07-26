<?php

namespace Cron\Tests;

use Cron\DayOfWeekField;
use DateTime;

/**
 * @author Michael Dowling <mtdowling@gmail.com>
 */
class DayOfWeekFieldTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Cron\DayOfWeekField::validate
     */
    public function testValdatesField()
    {
        $f = new DayOfWeekField();
        $this->assertTrue($f->validate('1'));
        $this->assertTrue($f->validate('*'));
        $this->assertTrue($f->validate('*/3,1,1-12'));
        $this->assertTrue($f->validate('SUN-2'));
    }

    /**
     * @covers Cron\DayOfWeekField::isSatisfiedBy
     */
    public function testChecksIfSatisfied()
    {
        $f = new DayOfWeekField();
        $this->assertTrue($f->isSatisfiedBy(new DateTime(), '?'));
    }
}