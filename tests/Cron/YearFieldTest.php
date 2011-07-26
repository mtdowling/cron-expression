<?php

namespace Cron\Tests;

use Cron\YearField;

/**
 * @author Michael Dowling <mtdowling@gmail.com>
 */
class YearFieldTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Cron\YearField::validate
     */
    public function testValdatesField()
    {
        $f = new YearField();
        $this->assertTrue($f->validate('2011'));
        $this->assertTrue($f->validate('*'));
        $this->assertTrue($f->validate('*/10,2012,1-12'));
    }
}