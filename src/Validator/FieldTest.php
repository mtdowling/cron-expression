<?php

declare(strict_types=1);

namespace Bakame\Cron\Validator;

use PHPUnit\Framework\TestCase;

/**
 * @author Michael Dowling <mtdowling@gmail.com>
 */
final class FieldTest extends TestCase
{
    /**
     * @covers \Bakame\Cron\Validator\Field::isSatisfied
     * @covers \Bakame\Cron\Validator\Field::isIncrementsOfRanges
     * @covers \Bakame\Cron\Validator\Field::isInIncrementsOfRanges
     * @covers \Bakame\Cron\Validator\Field::isInRange
     * @covers \Bakame\Cron\Validator\Field::isRange
     */
    public function testTestsIfSatisfied(): void
    {
        $f = new DayOfWeek();
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
        $f = new Hours();
        self::assertTrue($f->validate($expression));
    }
}
