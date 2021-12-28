<?php

declare(strict_types=1);

namespace Bakame\Cron;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Bakame\Cron\FieldValidator
 */
final class FieldValidatorTest extends TestCase
{
    public function testTestsIfSatisfied(): void
    {
        $f = new DayOfWeekValidator();
        self::assertTrue($f->isSatisfied(12, '3-13'));
        self::assertFalse($f->isSatisfied(15, '3-7/2'));
        self::assertTrue($f->isSatisfied(12, '*'));
        self::assertTrue($f->isSatisfied(12, '12'));
        self::assertFalse($f->isSatisfied(12, '3-11'));
        self::assertFalse($f->isSatisfied(12, '3-7/2'));
        self::assertFalse($f->isSatisfied(12, '11'));
    }

    public function testAllowRangesAndLists(): void
    {
        $expression = '5-7,11-13';
        $f = new HourValidator();
        self::assertTrue($f->isValid($expression));
    }
}
