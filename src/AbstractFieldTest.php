<?php

declare(strict_types=1);

namespace Cron;

use PHPUnit\Framework\TestCase;

/**
 * @author Michael Dowling <mtdowling@gmail.com>
 */
final class AbstractFieldTest extends TestCase
{
    /**
     * @covers \Cron\AbstractField::isSatisfied
     * @covers \Cron\AbstractField::isIncrementsOfRanges
     * @covers \Cron\AbstractField::isInIncrementsOfRanges
     * @covers \Cron\AbstractField::isInRange
     * @covers \Cron\AbstractField::isRange
     */
    public function testTestsIfSatisfied(): void
    {
        $f = new DayOfWeekField();
        self::assertTrue($f->isSatisfied(12, '3-13'));
        self::assertFalse($f->isSatisfied(15, '3-7/2'));
        self::assertTrue($f->isSatisfied(12, '*'));
        self::assertTrue($f->isSatisfied(12, '12'));
        self::assertFalse($f->isSatisfied(12, '3-11'));
        self::assertFalse($f->isSatisfied(12, '3-7/2'));
        self::assertFalse($f->isSatisfied(12, '11'));
    }
    /**
     * Allows ranges and lists to coexist in the same expression.
     *
     * @see https://github.com/dragonmantank/cron-expression/issues/5
     */
    public function testAllowRangesAndLists(): void
    {
        $expression = '5-7,11-13';
        $f = new HoursField();
        self::assertTrue($f->validate($expression));
    }
}
