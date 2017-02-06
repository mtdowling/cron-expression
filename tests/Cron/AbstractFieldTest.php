<?php

namespace Cron\Tests;

use Cron\DayOfWeekField;
use PHPUnit\Framework\TestCase;

/**
 * @author Michael Dowling <mtdowling@gmail.com>
 */
class AbstractFieldTest extends TestCase
{
    /**
     * @var \Cron\DayOfWeekField
     */
    protected $field;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->field = new DayOfWeekField();
    }

    /**
     * @covers \Cron\AbstractField::isRange
     */
    public function testTestsIfRange()
    {
        $this->assertTrue($this->field->isRange('1-2'));
        $this->assertFalse($this->field->isRange('2'));
    }

    /**
     * @covers \Cron\AbstractField::isIncrementsOfRanges
     */
    public function testTestsIfIncrementsOfRanges()
    {
        $this->assertFalse($this->field->isIncrementsOfRanges('1-2'));
        $this->assertTrue($this->field->isIncrementsOfRanges('1/2'));
        $this->assertTrue($this->field->isIncrementsOfRanges('*/2'));
        $this->assertTrue($this->field->isIncrementsOfRanges('3-12/2'));
    }

    /**
     * @covers \Cron\AbstractField::isInRange
     */
    public function testTestsIfInRange()
    {
        $this->assertTrue($this->field->isInRange('1', '1-2'));
        $this->assertTrue($this->field->isInRange('2', '1-2'));
        $this->assertTrue($this->field->isInRange('5', '4-12'));
        $this->assertFalse($this->field->isInRange('3', '4-12'));
        $this->assertFalse($this->field->isInRange('13', '4-12'));
    }

    /**
     * @covers \Cron\AbstractField::isInIncrementsOfRanges
     */
    public function testTestsIfInIncrementsOfRanges()
    {
        $this->assertTrue($this->field->isInIncrementsOfRanges('3', '3-59/2'));
        $this->assertTrue($this->field->isInIncrementsOfRanges('13', '3-59/2'));
        $this->assertTrue($this->field->isInIncrementsOfRanges('15', '3-59/2'));
        $this->assertTrue($this->field->isInIncrementsOfRanges('14', '*/2'));
        $this->assertFalse($this->field->isInIncrementsOfRanges('2', '3-59/13'));
        $this->assertFalse($this->field->isInIncrementsOfRanges('14', '*/13'));
        $this->assertFalse($this->field->isInIncrementsOfRanges('14', '3-59/2'));
        $this->assertFalse($this->field->isInIncrementsOfRanges('3', '2-59'));
        $this->assertFalse($this->field->isInIncrementsOfRanges('3', '2'));
        $this->assertFalse($this->field->isInIncrementsOfRanges('3', '*'));
        $this->assertFalse($this->field->isInIncrementsOfRanges('0', '*/0'));
        $this->assertFalse($this->field->isInIncrementsOfRanges('1', '*/0'));

        $this->assertTrue($this->field->isInIncrementsOfRanges('4', '4/10'));
        $this->assertTrue($this->field->isInIncrementsOfRanges('14', '4/10'));
        $this->assertTrue($this->field->isInIncrementsOfRanges('34', '4/10'));
    }

    /**
     * @covers \Cron\AbstractField::isSatisfied
     */
    public function testTestsIfSatisfied()
    {
        $this->assertTrue($this->field->isSatisfied('12', '3-13'));
        $this->assertTrue($this->field->isSatisfied('15', '3-59/12'));
        $this->assertTrue($this->field->isSatisfied('12', '*'));
        $this->assertTrue($this->field->isSatisfied('12', '12'));
        $this->assertFalse($this->field->isSatisfied('12', '3-11'));
        $this->assertFalse($this->field->isSatisfied('12', '3-59/13'));
        $this->assertFalse($this->field->isSatisfied('12', '11'));
    }
}
