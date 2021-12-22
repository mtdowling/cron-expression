<?php

declare(strict_types=1);

namespace Cron;

use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * @author Michael Dowling <mtdowling@gmail.com>
 */
final class HoursFieldTest extends TestCase
{
    /**
     * @covers \Cron\HoursField::validate
     */
    public function testValidatesField(): void
    {
        $f = new HoursField();
        self::assertTrue($f->validate('1'));
        self::assertTrue($f->validate('*'));
        self::assertFalse($f->validate('*/3,1,1-12'));
    }

    /**
     * @covers \Cron\HoursField::increment
     * @covers \Cron\AbstractField::computePosition
     */
    public function testIncrementsDate(): void
    {
        $d = new DateTime('2011-03-15 11:15:00');
        $f = new HoursField();
        $f->increment($d);
        self::assertSame('2011-03-15 12:00:00', $d->format('Y-m-d H:i:s'));

        $d->setTime(11, 15, 0);
        $f->increment($d, true);
        self::assertSame('2011-03-15 10:59:00', $d->format('Y-m-d H:i:s'));
    }

    /**
     * @covers \Cron\HoursField::increment
     * @covers \Cron\AbstractField::computePosition
     */
    public function testIncrementsDateWithThirtyMinuteOffsetTimezone(): void
    {
        $tz = date_default_timezone_get();
        date_default_timezone_set('America/St_Johns');
        $d = new DateTime('2011-03-15 11:15:00');
        $f = new HoursField();
        self::assertSame('2011-03-15 12:00:00', $f->increment($d)->format('Y-m-d H:i:s'));

        $d->setTime(11, 15, 0);
        self::assertSame('2011-03-15 10:59:00', $f->increment($d, true)->format('Y-m-d H:i:s'));
        date_default_timezone_set($tz);
    }

    /**
     * @covers \Cron\HoursField::increment
     * @covers \Cron\AbstractField::computePosition
     */
    public function testIncrementDateWithFifteenMinuteOffsetTimezone(): void
    {
        $tz = date_default_timezone_get();
        date_default_timezone_set('Asia/Kathmandu');
        $d = new DateTime('2011-03-15 11:15:00');
        $f = new HoursField();
        self::assertSame('2011-03-15 12:00:00', $f->increment($d)->format('Y-m-d H:i:s'));

        $d->setTime(11, 15, 0);
        self::assertSame('2011-03-15 10:59:00', $f->increment($d, true)->format('Y-m-d H:i:s'));
        date_default_timezone_set($tz);
    }
}
