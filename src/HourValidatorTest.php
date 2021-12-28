<?php

declare(strict_types=1);

namespace Bakame\Cron;

use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Bakame\Cron\HourValidator
 */
final class HourValidatorTest extends TestCase
{
    public function testValidatesField(): void
    {
        $f = new HourValidator();
        self::assertTrue($f->isValid('1'));
        self::assertTrue($f->isValid('*'));
        self::assertFalse($f->isValid('*/3,1,1-12'));
    }

    public function testIncrementsDate(): void
    {
        $d = new DateTime('2011-03-15 11:15:00');
        $f = new HourValidator();
        $f->increment($d);
        self::assertSame('2011-03-15 12:00:00', $d->format('Y-m-d H:i:s'));

        $d->setTime(11, 15, 0);
        $f->increment($d, true);
        self::assertSame('2011-03-15 10:59:00', $d->format('Y-m-d H:i:s'));
    }

    public function testIncrementsDateWithThirtyMinuteOffsetTimezone(): void
    {
        $tz = date_default_timezone_get();
        date_default_timezone_set('America/St_Johns');
        $d = new DateTime('2011-03-15 11:15:00');
        $f = new HourValidator();
        self::assertSame('2011-03-15 12:00:00', $f->increment($d)->format('Y-m-d H:i:s'));

        $d->setTime(11, 15, 0);
        self::assertSame('2011-03-15 10:59:00', $f->increment($d, true)->format('Y-m-d H:i:s'));
        date_default_timezone_set($tz);
    }

    public function testIncrementDateWithFifteenMinuteOffsetTimezone(): void
    {
        $tz = date_default_timezone_get();
        date_default_timezone_set('Asia/Kathmandu');
        $d = new DateTime('2011-03-15 11:15:00');
        $f = new HourValidator();
        self::assertSame('2011-03-15 12:00:00', $f->increment($d)->format('Y-m-d H:i:s'));

        $d->setTime(11, 15, 0);
        self::assertSame('2011-03-15 10:59:00', $f->increment($d, true)->format('Y-m-d H:i:s'));
        date_default_timezone_set($tz);
    }
}
