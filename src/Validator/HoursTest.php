<?php

declare(strict_types=1);

namespace Bakame\Cron\Validator;

use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * @author Michael Dowling <mtdowling@gmail.com>
 */
final class HoursTest extends TestCase
{
    /**
     * @covers \Bakame\Cron\Validator\Hours::validate
     */
    public function testValidatesField(): void
    {
        $f = new Hours();
        self::assertTrue($f->validate('1'));
        self::assertTrue($f->validate('*'));
        self::assertFalse($f->validate('*/3,1,1-12'));
    }

    /**
     * @covers \Bakame\Cron\Validator\Hours::increment
     * @covers \Bakame\Cron\Validator\CronField::computePosition
     */
    public function testIncrementsDate(): void
    {
        $d = new DateTime('2011-03-15 11:15:00');
        $f = new Hours();
        $f->increment($d);
        self::assertSame('2011-03-15 12:00:00', $d->format('Y-m-d H:i:s'));

        $d->setTime(11, 15, 0);
        $f->increment($d, true);
        self::assertSame('2011-03-15 10:59:00', $d->format('Y-m-d H:i:s'));
    }

    /**
     * @covers \Bakame\Cron\Validator\Hours::increment
     * @covers \Bakame\Cron\Validator\CronField::computePosition
     */
    public function testIncrementsDateWithThirtyMinuteOffsetTimezone(): void
    {
        $tz = date_default_timezone_get();
        date_default_timezone_set('America/St_Johns');
        $d = new DateTime('2011-03-15 11:15:00');
        $f = new Hours();
        self::assertSame('2011-03-15 12:00:00', $f->increment($d)->format('Y-m-d H:i:s'));

        $d->setTime(11, 15, 0);
        self::assertSame('2011-03-15 10:59:00', $f->increment($d, true)->format('Y-m-d H:i:s'));
        date_default_timezone_set($tz);
    }

    /**
     * @covers \Bakame\Cron\Validator\Hours::increment
     * @covers \Bakame\Cron\Validator\CronField::computePosition
     */
    public function testIncrementDateWithFifteenMinuteOffsetTimezone(): void
    {
        $tz = date_default_timezone_get();
        date_default_timezone_set('Asia/Kathmandu');
        $d = new DateTime('2011-03-15 11:15:00');
        $f = new Hours();
        self::assertSame('2011-03-15 12:00:00', $f->increment($d)->format('Y-m-d H:i:s'));

        $d->setTime(11, 15, 0);
        self::assertSame('2011-03-15 10:59:00', $f->increment($d, true)->format('Y-m-d H:i:s'));
        date_default_timezone_set($tz);
    }
}
