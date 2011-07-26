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
}