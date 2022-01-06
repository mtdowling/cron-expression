<?php

declare(strict_types=1);

namespace Cron\Tests;

use Cron\MinutesField;
use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * @author Michael Dowling <mtdowling@gmail.com>
 */
class MinutesFieldTest extends TestCase
{
    /**
     * @covers \Cron\MinutesField::validate
     */
    public function testValidatesField(): void
    {
        $f = new MinutesField();
        $this->assertTrue($f->validate('1'));
        $this->assertTrue($f->validate('*'));
        $this->assertTrue($f->validate('*/3,1,1-12'));
        $this->assertFalse($f->validate('1/10'));
    }

    /**
     * @covers \Cron\MinutesField::isSatisfiedBy
     */
    public function testChecksIfSatisfied(): void
    {
        $f = new MinutesField();
        $this->assertTrue($f->isSatisfiedBy(new DateTime(), '?', false));
        $this->assertTrue($f->isSatisfiedBy(new DateTimeImmutable(), '?', false));
    }

    /**
     * @covers \Cron\MinutesField::increment
     */
    public function testIncrementsDate(): void
    {
        $d = new DateTime('2011-03-15 11:15:00');
        $f = new MinutesField();
        $f->increment($d);
        $this->assertSame('2011-03-15 11:16:00', $d->format('Y-m-d H:i:s'));

        $f->increment($d, true);
        $this->assertSame('2011-03-15 11:15:00', $d->format('Y-m-d H:i:s'));
    }

    /**
     * @covers \Cron\MinutesField::increment
     */
    public function testIncrementsDateTimeImmutable(): void
    {
        $d = new DateTimeImmutable('2011-03-15 11:15:00');
        $f = new MinutesField();
        $f->increment($d);
        $this->assertSame('2011-03-15 11:16:00', $d->format('Y-m-d H:i:s'));
    }

    /**
     * Various bad syntaxes that are reported to work, but shouldn't.
     *
     * @author Chris Tankersley
     *
     * @since 2017-08-18
     */
    public function testBadSyntaxesShouldNotValidate(): void
    {
        $f = new MinutesField();
        $this->assertFalse($f->validate('*-1'));
        $this->assertFalse($f->validate('1-2-3'));
        $this->assertFalse($f->validate('-1'));
    }

    /**
     * Ranges that are invalid should not validate.
     * In this case `0/5` would be invalid because `0` is not part of the minute range.
     *
     * @author Chris Tankersley
     * @since 2019-07-29
     * @see https://github.com/dragonmantank/cron-expression/issues/18
     */
    public function testInvalidRangeShouldNotValidate(): void
    {
        $f = new MinutesField();
        $this->assertFalse($f->validate('0/5'));
    }

    /**
     * @covers \Cron\MinutesField::increment
     */
    public function testIncrementAcrossDstChangeBerlin(): void
    {
        $tz = new \DateTimeZone("Europe/Berlin");
        $d = \DateTimeImmutable::createFromFormat("!Y-m-d H:i:s", "2021-03-28 01:59:00", $tz);
        $f = new MinutesField();
        $f->increment($d);
        $this->assertSame("2021-03-28 03:00:00", $d->format("Y-m-d H:i:s"));

        $f->increment($d, true);
        $this->assertSame("2021-03-28 01:59:00", $d->format("Y-m-d H:i:s"));
        $f->increment($d, true);
        $this->assertSame("2021-03-28 01:58:00", $d->format("Y-m-d H:i:s"));
    }

    /**
     * @covers \Cron\MinutesField::increment
     */
    public function testIncrementAcrossDstChangeLondon(): void
    {
        $tz = new \DateTimeZone("Europe/London");
        $d = \DateTimeImmutable::createFromFormat("!Y-m-d H:i:s", "2021-03-28 00:59:00", $tz);
        $f = new MinutesField();
        $f->increment($d);
        $this->assertSame("2021-03-28 02:00:00", $d->format("Y-m-d H:i:s"));
        $f->increment($d);
        $this->assertSame("2021-03-28 02:01:00", $d->format("Y-m-d H:i:s"));

        $f->increment($d, true);
        $this->assertSame("2021-03-28 02:00:00", $d->format("Y-m-d H:i:s"));
        $f->increment($d, true);
        $this->assertSame("2021-03-28 00:59:00", $d->format("Y-m-d H:i:s"));
        $f->increment($d, true);
        $this->assertSame("2021-03-28 00:58:00", $d->format("Y-m-d H:i:s"));
    }
}
