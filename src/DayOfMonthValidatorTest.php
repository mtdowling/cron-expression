<?php

declare(strict_types=1);

namespace Bakame\Cron;

use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Bakame\Cron\DayOfMonthValidator
 */
final class DayOfMonthValidatorTest extends TestCase
{
    public function testValidatesField(): void
    {
        $f = new DayOfMonthValidator();
        self::assertTrue($f->isValid('1'));
        self::assertTrue($f->isValid('*'));
        self::assertTrue($f->isValid('L'));
        self::assertTrue($f->isValid('5W'));
        self::assertFalse($f->isValid('5W,L'));
        self::assertFalse($f->isValid('1.'));
    }

    public function testChecksIfSatisfied(): void
    {
        $f = new DayOfMonthValidator();
        self::assertTrue($f->isSatisfiedBy(new DateTime(), '?'));
    }

    public function testIncrementsDate(): void
    {
        $f = new DayOfMonthValidator();
        $date = '2011-03-15 11:15:00';
        self::assertSame(
            '2011-03-16 00:00:00',
            $f->increment(new DateTime($date))->format('Y-m-d H:i:s')
        );
    }

    public function testDecrementsDate(): void
    {
        $f = new DayOfMonthValidator();
        $date = '2011-03-15 11:15:00';
        self::assertSame(
            '2011-03-14 23:59:00',
            $f->increment(new DateTime($date), true)->format('Y-m-d H:i:s')
        );
    }

    public function testDoesNotAccept0Date(): void
    {
        $f = new DayOfMonthValidator();
        self::assertFalse($f->isValid('0'));
    }
}
