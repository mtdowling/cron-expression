<?php

declare(strict_types=1);

namespace Cron\Tests;

use Cron\HoursField;
use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * @author Michael Dowling <mtdowling@gmail.com>
 */
class HoursFieldTest extends TestCase
{
    /**
     * @covers \Cron\HoursField::validate
     */
    public function testValidatesField(): void
    {
        $f = new HoursField();
        $this->assertTrue($f->validate('1'));
        $this->assertTrue($f->validate('00'));
        $this->assertTrue($f->validate('01'));
        $this->assertTrue($f->validate('*'));
        $this->assertTrue($f->validate('*/3,1,1-12'));
        $this->assertFalse($f->validate('1/10'));
    }

    /**
     * @covers \Cron\HoursField::increment
     */
    public function testIncrementsDate(): void
    {
        $d = new DateTime('2011-03-15 11:15:00');
        $f = new HoursField();
        $f->increment($d);
        $this->assertSame('2011-03-15 12:00:00', $d->format('Y-m-d H:i:s'));

        $d->setTime(11, 15, 0);
        $f->increment($d, true);
        $this->assertSame('2011-03-15 10:59:00', $d->format('Y-m-d H:i:s'));
    }

    /**
     * @covers \Cron\HoursField::increment
     */
    public function testIncrementsDateTimeImmutable(): void
    {
        $d = new DateTimeImmutable('2011-03-15 11:15:00');
        $f = new HoursField();
        $f->increment($d);
        $this->assertSame('2011-03-15 12:00:00', $d->format('Y-m-d H:i:s'));
    }

    /**
     * @covers \Cron\HoursField::increment
     */
    public function testIncrementsDateWithThirtyMinuteOffsetTimezone(): void
    {
        $tz = date_default_timezone_get();
        date_default_timezone_set('America/St_Johns');
        $d = new DateTime('2011-03-15 11:15:00');
        $f = new HoursField();
        $f->increment($d);
        $this->assertSame('2011-03-15 12:00:00', $d->format('Y-m-d H:i:s'));

        $d->setTime(11, 15, 0);
        $f->increment($d, true);
        $this->assertSame('2011-03-15 10:59:00', $d->format('Y-m-d H:i:s'));
        date_default_timezone_set($tz);
    }

    /**
     * @covers \Cron\HoursField::increment
     */
    public function testIncrementDateWithFifteenMinuteOffsetTimezone(): void
    {
        $tz = date_default_timezone_get();
        date_default_timezone_set('Asia/Kathmandu');
        $d = new DateTime('2011-03-15 11:15:00');
        $f = new HoursField();
        $f->increment($d);
        $this->assertSame('2011-03-15 12:00:00', $d->format('Y-m-d H:i:s'));

        $d->setTime(11, 15, 0);
        $f->increment($d, true);
        $this->assertSame('2011-03-15 10:59:00', $d->format('Y-m-d H:i:s'));
        date_default_timezone_set($tz);
    }

    /**
     * @covers \Cron\HoursField::increment
     */
    public function testIncrementAcrossDstChangeBerlin(): void
    {
        $tz = new \DateTimeZone("Europe/Berlin");
        $d = \DateTimeImmutable::createFromFormat("!Y-m-d H:i:s", "2021-03-27 23:00:00", $tz);
        $f = new HoursField();
        $f->increment($d);
        $this->assertSame("2021-03-28 00:00:00", $d->format("Y-m-d H:i:s"));
        $f->increment($d);
        $this->assertSame("2021-03-28 01:00:00", $d->format("Y-m-d H:i:s"));
        $f->increment($d);
        $this->assertSame("2021-03-28 03:00:00", $d->format("Y-m-d H:i:s"));

        $f->increment($d, true);
        $this->assertSame("2021-03-28 01:59:00", $d->format("Y-m-d H:i:s"));
        $f->increment($d, true);
        $this->assertSame("2021-03-28 00:59:00", $d->format("Y-m-d H:i:s"));
        $f->increment($d, true);
        $this->assertSame("2021-03-27 23:59:00", $d->format("Y-m-d H:i:s"));
    }

    /**
     * @covers \Cron\HoursField::increment
     */
    public function testIncrementAcrossDstChangeLondon(): void
    {
        $tz = new \DateTimeZone("Europe/London");
        $d = \DateTimeImmutable::createFromFormat("!Y-m-d H:i:s", "2021-03-27 23:00:00", $tz);
        $f = new HoursField();
        $f->increment($d);
        $this->assertSame("2021-03-28 00:00:00", $d->format("Y-m-d H:i:s"));
        $f->increment($d);
        $this->assertSame("2021-03-28 02:00:00", $d->format("Y-m-d H:i:s"));
        $f->increment($d);
        $this->assertSame("2021-03-28 03:00:00", $d->format("Y-m-d H:i:s"));

        $f->increment($d, true);
        $this->assertSame("2021-03-28 02:59:00", $d->format("Y-m-d H:i:s"));
        $f->increment($d, true);
        $this->assertSame("2021-03-28 00:59:00", $d->format("Y-m-d H:i:s"));
        $f->increment($d, true);
        $this->assertSame("2021-03-27 23:59:00", $d->format("Y-m-d H:i:s"));
    }
}
