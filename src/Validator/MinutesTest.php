<?php

declare(strict_types=1);

namespace Bakame\Cron\Validator;

use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * @author Michael Dowling <mtdowling@gmail.com>
 */
final class MinutesTest extends TestCase
{
    /**
     * @covers \Bakame\Cron\Validator\Minutes::validate
     */
    public function testValidatesField(): void
    {
        $f = new Minutes();
        self::assertTrue($f->validate('1'));
        self::assertTrue($f->validate('*'));
        self::assertFalse($f->validate('*/3,1,1-12'));
    }

    /**
     * @covers \Bakame\Cron\Validator\Minutes::increment
     * @covers \Bakame\Cron\Validator\Field::computePosition
     */
    public function testIncrementsDate(): void
    {
        $d = new DateTime('2011-03-15 11:15:00');
        $f = new Minutes();

        self::assertSame('2011-03-15 11:16:00', $f->increment($d)->format('Y-m-d H:i:s'));
        self::assertSame('2011-03-15 11:15:00', $f->increment($d, true)->format('Y-m-d H:i:s'));
    }

    /**
     * Various bad syntaxes that are reported to work, but shouldn't.
     *
     * @author Chris Tankersley
     * @since 2017-08-18
     */
    public function testBadSyntaxesShouldNotValidate(): void
    {
        $f = new Minutes();
        self::assertFalse($f->validate('*-1'));
        self::assertFalse($f->validate('1-2-3'));
        self::assertFalse($f->validate('-1'));
    }
}
