<?php

declare(strict_types=1);

namespace Bakame\Cron\Validator;

use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Bakame\Cron\Validator\DayOfMonth
 */
final class DayOfMonthTest extends TestCase
{
    /**
     * @covers \Bakame\Cron\Validator\DayOfMonth::validate
     */
    public function testValidatesField(): void
    {
        $f = new DayOfMonth();
        self::assertTrue($f->validate('1'));
        self::assertTrue($f->validate('*'));
        self::assertTrue($f->validate('L'));
        self::assertTrue($f->validate('5W'));
        self::assertFalse($f->validate('5W,L'));
        self::assertFalse($f->validate('1.'));
    }

    /**
     * @covers \Bakame\Cron\Validator\DayOfMonth::isSatisfiedBy
     */
    public function testChecksIfSatisfied(): void
    {
        $f = new DayOfMonth();
        self::assertTrue($f->isSatisfiedBy(new DateTime(), '?'));
    }

    /**
     * @covers \Bakame\Cron\Validator\DayOfMonth::increment
     */
    public function testIncrementsDate(): void
    {
        $f = new DayOfMonth();
        $date = '2011-03-15 11:15:00';
        self::assertSame(
            '2011-03-16 00:00:00',
            $f->increment(new DateTime($date))->format('Y-m-d H:i:s')
        );
    }
    /**
     * @covers \Bakame\Cron\Validator\DayOfMonth::increment
     */
    public function testDecrementsDate(): void
    {
        $f = new DayOfMonth();
        $date = '2011-03-15 11:15:00';
        self::assertSame(
            '2011-03-14 23:59:00',
            $f->increment(new DateTime($date), true)->format('Y-m-d H:i:s')
        );
    }

    /**
     * Day of the month cannot accept a 0 value, it must be between 1 and 31
     * See Github issue #120.
     *
     * @since 2017-01-22
     */
    public function testDoesNotAccept0Date(): void
    {
        $f = new DayOfMonth();
        self::assertFalse($f->validate('0'));
    }
}
