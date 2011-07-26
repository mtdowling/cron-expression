<?php

namespace Cron\Tests;

use Cron\HoursField;

/**
 * @author Michael Dowling <mtdowling@gmail.com>
 */
class HoursFieldTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Cron\HoursField::validate
     */
    public function testValdatesField()
    {
        $f = new HoursField();
        $this->assertTrue($f->validate('1'));
        $this->assertTrue($f->validate('*'));
        $this->assertTrue($f->validate('*/3,1,1-12'));    }
}