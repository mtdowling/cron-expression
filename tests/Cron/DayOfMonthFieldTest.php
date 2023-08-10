<?php

declare(strict_types=1);

namespace Cron\Tests;

use Cron\DayOfMonthField;
use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * @author Michael Dowling <mtdowling@gmail.com>
 */
class DayOfMonthFieldTest extends TestCase
{
    /**
     * @covers \Cron\DayOfMonthField::validate
     */
    public function testValidatesField(): void
    {
        $f = new DayOfMonthField();
        $this->assertTrue($f->validate('1'));
        $this->assertTrue($f->validate('*'));
        $this->assertTrue($f->validate('L'));
        $this->assertTrue($f->validate('5W'));
        $this->assertTrue($f->validate('?'));
        $this->assertTrue($f->validate('01'));
        $this->assertFalse($f->validate('5W,L'));
        $this->assertFalse($f->validate('1.'));
    }

    /**
     * @covers \Cron\DayOfMonthField::isSatisfiedBy
     */
    public function testChecksIfSatisfied(): void
    {
        $f = new DayOfMonthField();
        $this->assertTrue($f->isSatisfiedBy(new DateTime(), '?', false));
        $this->assertTrue($f->isSatisfiedBy(new DateTimeImmutable(), '?', false));
    }

    /**
     * @covers \Cron\DayOfMonthField::increment
     */
    public function testIncrementsDate(): void
    {
        $d = new DateTime('2011-03-15 11:15:00');
        $f = new DayOfMonthField();
        $f->increment($d);
        $this->assertSame('2011-03-16 00:00:00', $d->format('Y-m-d H:i:s'));

        $d = new DateTime('2011-03-15 11:15:00');
        $f->increment($d, true);
        $this->assertSame('2011-03-14 23:59:00', $d->format('Y-m-d H:i:s'));
    }

    /**
     * @covers \Cron\DayOfMonthField::increment
     */
    public function testIncrementsDateTimeImmutable(): void
    {
        $d = new DateTimeImmutable('2011-03-15 11:15:00');
        $f = new DayOfMonthField();
        $f->increment($d);
        $this->assertSame('2011-03-16 00:00:00', $d->format('Y-m-d H:i:s'));
    }

    /**
     * Day of the month cannot accept a 0 value, it must be between 1 and 31
     * See Github issue #120.
     *
     * @since 2017-01-22
     */
    public function testDoesNotAccept0Date(): void
    {
        $f = new DayOfMonthField();
        $this->assertFalse($f->validate('0'));
    }

    /**
     * @covers \Cron\MinutesField::increment
     */
    public function testIncrementAcrossDstChangeBerlin(): void
    {
        $tz = new \DateTimeZone("Europe/Berlin");
        $d = \DateTimeImmutable::createFromFormat("!Y-m-d H:i:s", "2021-03-28 01:59:00", $tz);
        $f = new DayOfMonthField();
        $f->increment($d);
        $this->assertSame("2021-03-29 00:00:00", $d->format("Y-m-d H:i:s"));

        $f->increment($d, true);
        $this->assertSame("2021-03-28 23:59:00", $d->format("Y-m-d H:i:s"));
        $f->increment($d, true);
        $this->assertSame("2021-03-27 23:59:00", $d->format("Y-m-d H:i:s"));
    }

    /**
     * @covers \Cron\MinutesField::increment
     */
    public function testIncrementAcrossDstChangeLondon(): void
    {
        $tz = new \DateTimeZone("Europe/London");
        $d = \DateTimeImmutable::createFromFormat("!Y-m-d H:i:s", "2021-03-28 00:59:00", $tz);
        $f = new DayOfMonthField();
        $f->increment($d);
        $this->assertSame("2021-03-29 00:00:00", $d->format("Y-m-d H:i:s"));
        $f->increment($d);
        $this->assertSame("2021-03-30 00:00:00", $d->format("Y-m-d H:i:s"));

        $f->increment($d, true);
        $this->assertSame("2021-03-29 23:59:00", $d->format("Y-m-d H:i:s"));
        $f->increment($d, true);
        $this->assertSame("2021-03-28 23:59:00", $d->format("Y-m-d H:i:s"));
        $f->increment($d, true);
        $this->assertSame("2021-03-27 23:59:00", $d->format("Y-m-d H:i:s"));
    }

    public function testIssue151DOMFieldSupportLW()
    {
        $f = new DayOfMonthField();
        $this->assertTrue($f->validate('LW'));
    }
}
