<?php

namespace Cron\Tests;

use Cron\MinutesField;

/**
 * @author Michael Dowling <mtdowling@gmail.com>
 */
class MinutesFieldTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Cron\MinutesField::validate
     */
    public function testValdatesField()
    {
        $f = new MinutesField();
        $this->assertTrue($f->validate('1'));
        $this->assertTrue($f->validate('*'));
        $this->assertTrue($f->validate('*/3,1,1-12'));    }
}