<?php

declare(strict_types=1);

namespace Cron;

use OutOfRangeException;
use PHPUnit\Framework\TestCase;

/**
 * @author Michael Dowling <mtdowling@gmail.com>
 */
final class AbstractFieldTest extends TestCase
{
    /**
     * @covers \Cron\AbstractField::isRange
     */
    public function testTestsIfRange(): void
    {
        $f = new DayOfWeekField();
        self::assertTrue($f->isRange('1-2'));
        self::assertFalse($f->isRange('2'));
    }

    /**
     * @covers \Cron\AbstractField::isIncrementsOfRanges
     */
    public function testTestsIfIncrementsOfRanges(): void
    {
        $f = new DayOfWeekField();
        self::assertFalse($f->isIncrementsOfRanges('1-2'));
        self::assertTrue($f->isIncrementsOfRanges('1/2'));
        self::assertTrue($f->isIncrementsOfRanges('*/2'));
        self::assertTrue($f->isIncrementsOfRanges('3-12/2'));
    }

    /**
     * @covers \Cron\AbstractField::isInRange
     */
    public function testTestsIfInRange(): void
    {
        $f = new DayOfWeekField();
        self::assertTrue($f->isInRange('1', '1-2'));
        self::assertTrue($f->isInRange('2', '1-2'));
        self::assertTrue($f->isInRange('5', '4-12'));
        self::assertFalse($f->isInRange('3', '4-12'));
        self::assertFalse($f->isInRange('13', '4-12'));
    }

    /**
     * @covers \Cron\AbstractField::isInIncrementsOfRanges
     */
    public function testTestsIfInIncrementsOfRangesOnZeroStartRange(): void
    {
        $f = new MinutesField();
        self::assertTrue($f->isInIncrementsOfRanges('3', '3-59/2'));
        self::assertTrue($f->isInIncrementsOfRanges('13', '3-59/2'));
        self::assertTrue($f->isInIncrementsOfRanges('15', '3-59/2'));
        self::assertTrue($f->isInIncrementsOfRanges('14', '*/2'));
        self::assertFalse($f->isInIncrementsOfRanges('2', '3-59/13'));
        self::assertFalse($f->isInIncrementsOfRanges('14', '*/13'));
        self::assertFalse($f->isInIncrementsOfRanges('14', '3-59/2'));
        self::assertFalse($f->isInIncrementsOfRanges('3', '2-59'));
        self::assertFalse($f->isInIncrementsOfRanges('3', '2'));
        self::assertFalse($f->isInIncrementsOfRanges('3', '*'));
        self::assertFalse($f->isInIncrementsOfRanges('0', '*/0'));
        self::assertFalse($f->isInIncrementsOfRanges('1', '*/0'));

        self::assertTrue($f->isInIncrementsOfRanges('4', '4/1'));
        self::assertFalse($f->isInIncrementsOfRanges('14', '4/1'));
        self::assertFalse($f->isInIncrementsOfRanges('34', '4/1'));
    }

    /**
     * @covers \Cron\AbstractField::isInIncrementsOfRanges
     */
    public function testTestsIfInIncrementsOfRangesOnOneStartRange(): void
    {
        $f = new MonthField();
        self::assertTrue($f->isInIncrementsOfRanges('3', '3-12/2'));
        self::assertFalse($f->isInIncrementsOfRanges('13', '3-12/2'));
        self::assertFalse($f->isInIncrementsOfRanges('15', '3-12/2'));
        self::assertTrue($f->isInIncrementsOfRanges('3', '*/2'));
        self::assertFalse($f->isInIncrementsOfRanges('3', '*/3'));
        self::assertTrue($f->isInIncrementsOfRanges('7', '*/3'));
        self::assertFalse($f->isInIncrementsOfRanges('14', '3-12/2'));
        self::assertFalse($f->isInIncrementsOfRanges('3', '2-12'));
        self::assertFalse($f->isInIncrementsOfRanges('3', '2'));
        self::assertFalse($f->isInIncrementsOfRanges('3', '*'));
        self::assertFalse($f->isInIncrementsOfRanges('0', '*/0'));
        self::assertFalse($f->isInIncrementsOfRanges('1', '*/0'));

        self::assertTrue($f->isInIncrementsOfRanges('4', '4/1'));
        self::assertFalse($f->isInIncrementsOfRanges('14', '4/1'));
        self::assertFalse($f->isInIncrementsOfRanges('34', '4/1'));
    }

    public function testStepCannotBeLargerThanRange(): void
    {
        $this->expectException(OutOfRangeException::class);
        $f = new MonthField();
        $f->isInIncrementsOfRanges('2', '3-12/13');
    }

    /**
     * @covers \Cron\AbstractField::isSatisfied
     */
    public function testTestsIfSatisfied(): void
    {
        $f = new DayOfWeekField();
        self::assertTrue($f->isSatisfied('12', '3-13'));
        self::assertFalse($f->isSatisfied('15', '3-7/2'));
        self::assertTrue($f->isSatisfied('12', '*'));
        self::assertTrue($f->isSatisfied('12', '12'));
        self::assertFalse($f->isSatisfied('12', '3-11'));
        self::assertFalse($f->isSatisfied('12', '3-7/2'));
        self::assertFalse($f->isSatisfied('12', '11'));
    }
}
