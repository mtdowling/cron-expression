<?php

declare(strict_types=1);

namespace Cron\Tests;

use Cron\MonthField;
use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * @author Michael Dowling <mtdowling@gmail.com>
 */
class MonthFieldTest extends TestCase
{
    /**
     * @covers \Cron\MonthField::validate
     */
    public function testValidatesField(): void
    {
        $f = new MonthField();
        $this->assertTrue($f->validate('12'));
        $this->assertTrue($f->validate('*'));
        $this->assertTrue($f->validate('*/10,2,1-12'));
        $this->assertFalse($f->validate('1.fix-regexp'));
        $this->assertFalse($f->validate('1/10'));
    }

    /**
     * @covers \Cron\MonthField::isSatisfiedBy
     */
    public function testChecksIfSatisfied(): void
    {
        $f = new MonthField();
        $this->assertTrue($f->isSatisfiedBy(new DateTime(), '?', false));
        $this->assertTrue($f->isSatisfiedBy(new DateTimeImmutable(), '?', false));
    }

    /**
     * @covers \Cron\MonthField::increment
     */
    public function testIncrementsDate(): void
    {
        $d = new DateTime('2011-03-15 11:15:00');
        $f = new MonthField();
        $f->increment($d);
        $this->assertSame('2011-04-01 00:00:00', $d->format('Y-m-d H:i:s'));

        $d = new DateTime('2011-03-15 11:15:00');
        $f->increment($d, true);
        $this->assertSame('2011-02-28 23:59:00', $d->format('Y-m-d H:i:s'));
    }

    /**
     * @covers \Cron\MonthField::increment
     */
    public function testIncrementsDateTimeImmutable(): void
    {
        $d = new DateTimeImmutable('2011-03-15 11:15:00');
        $f = new MonthField();
        $f->increment($d);
        $this->assertSame('2011-04-01 00:00:00', $d->format('Y-m-d H:i:s'));
    }

    /**
     * @covers \Cron\MonthField::increment
     */
    public function testIncrementsDateWithThirtyMinuteTimezone(): void
    {
        $tz = date_default_timezone_get();
        date_default_timezone_set('America/St_Johns');
        $d = new DateTime('2011-03-31 11:59:59');
        $f = new MonthField();
        $f->increment($d);
        $this->assertSame('2011-04-01 00:00:00', $d->format('Y-m-d H:i:s'));

        $d = new DateTime('2011-03-15 11:15:00');
        $f->increment($d, true);
        $this->assertSame('2011-02-28 23:59:00', $d->format('Y-m-d H:i:s'));
        date_default_timezone_set($tz);
    }

    /**
     * @covers \Cron\MonthField::increment
     */
    public function testIncrementsYearAsNeeded(): void
    {
        $f = new MonthField();
        $d = new DateTime('2011-12-15 00:00:00');
        $f->increment($d);
        $this->assertSame('2012-01-01 00:00:00', $d->format('Y-m-d H:i:s'));
    }

    /**
     * @covers \Cron\MonthField::increment
     */
    public function testDecrementsYearAsNeeded(): void
    {
        $f = new MonthField();
        $d = new DateTime('2011-01-15 00:00:00');
        $f->increment($d, true);
        $this->assertSame('2010-12-31 23:59:00', $d->format('Y-m-d H:i:s'));
    }

    /**
     * Incoming literals should ignore case
     *
     * @author Chris Tankersley <chris@ctankersley.com?
     * @since 2019-07-29
     * @see https://github.com/dragonmantank/cron-expression/issues/24
     */
    public function testLiteralsIgnoreCasingProperly(): void
    {
        $f = new MonthField();
        $this->assertTrue($f->validate('JAN'));
        $this->assertTrue($f->validate('Jan'));
        $this->assertTrue($f->validate('jan'));
    }
}
