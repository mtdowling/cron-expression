<?php

declare(strict_types=1);

namespace Bakame\Cron;

use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Bakame\Cron\DayOfWeekValidator
 */
final class DayOfWeekValidatorTest extends TestCase
{
    public function testValidatesField(): void
    {
        $f = new DayOfWeekValidator();
        self::assertTrue($f->validate('1'));
        self::assertTrue($f->validate('*'));
        self::assertFalse($f->validate('*/3,1,1-12'));
        self::assertTrue($f->validate('SUN-2'));
        self::assertFalse($f->validate('1.'));
    }

    public function testChecksIfSatisfied(): void
    {
        $f = new DayOfWeekValidator();
        self::assertTrue($f->isSatisfiedBy(new DateTime(), '?'));
    }

    public function testIncrementsDate(): void
    {
        $f = new DayOfWeekValidator();
        self::assertSame('2011-03-16 00:00:00', $f->increment(new DateTime('2011-03-15 11:15:00'))->format('Y-m-d H:i:s'));

        self::assertSame('2011-03-14 23:59:00', $f->increment(new DateTime('2011-03-15 11:15:00'), true)->format('Y-m-d H:i:s'));
    }

    /**
     * @covers \Bakame\Cron\SyntaxError
     */
    public function testValidatesHashValueWeekday(): void
    {
        $this->expectException(SyntaxError::class);

        $f = new DayOfWeekValidator();
        self::assertTrue($f->isSatisfiedBy(new DateTime(), '12#1'));
    }

    /**
     * @covers \Bakame\Cron\SyntaxError
     */
    public function testValidatesHashValueNth(): void
    {
        $this->expectException(SyntaxError::class);
        $f = new DayOfWeekValidator();
        self::assertTrue($f->isSatisfiedBy(new DateTime(), '3#6'));
    }

    public function testValidateWeekendHash(): void
    {
        $f = new DayOfWeekValidator();
        self::assertTrue($f->validate('MON#1'));
        self::assertTrue($f->validate('TUE#2'));
        self::assertTrue($f->validate('WED#3'));
        self::assertTrue($f->validate('THU#4'));
        self::assertTrue($f->validate('FRI#5'));
        self::assertTrue($f->validate('SAT#1'));
        self::assertTrue($f->validate('SUN#3'));
        self::assertTrue($f->validate('MON#1,MON#3'));
    }

    public function testHandlesZeroAndSevenDayOfTheWeekValues(): void
    {
        $f = new DayOfWeekValidator();
        self::assertTrue($f->isSatisfiedBy(new DateTime('2011-09-04 00:00:00'), '0-2'));
        self::assertTrue($f->isSatisfiedBy(new DateTime('2011-09-04 00:00:00'), '6-0'));

        self::assertTrue($f->isSatisfiedBy(new DateTime('2014-04-20 00:00:00'), 'SUN'));
        self::assertTrue($f->isSatisfiedBy(new DateTime('2014-04-20 00:00:00'), 'SUN#3'));
        self::assertTrue($f->isSatisfiedBy(new DateTime('2014-04-20 00:00:00'), '0#3'));
        self::assertTrue($f->isSatisfiedBy(new DateTime('2014-04-20 00:00:00'), '7#3'));
    }

    public function testIssue47(): void
    {
        $f = new DayOfWeekValidator();
        self::assertFalse($f->validate('mon,'));
        self::assertFalse($f->validate('mon-'));
        self::assertFalse($f->validate('*/2,'));
        self::assertFalse($f->validate('-mon'));
        self::assertFalse($f->validate(',1'));
        self::assertFalse($f->validate('*-'));
        self::assertFalse($f->validate(',-'));
    }
}
