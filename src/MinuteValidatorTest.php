<?php

declare(strict_types=1);

namespace Bakame\Cron;

use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Bakame\Cron\MinuteValidator
 */
final class MinuteValidatorTest extends TestCase
{
    public function testValidatesField(): void
    {
        $f = new MinuteValidator();
        self::assertTrue($f->isValid('1'));
        self::assertTrue($f->isValid('*'));
        self::assertFalse($f->isValid('*/3,1,1-12'));
    }

    public function testIncrementsDate(): void
    {
        $d = new DateTime('2011-03-15 11:15:00');
        $f = new MinuteValidator();

        self::assertSame('2011-03-15 11:16:00', $f->increment($d)->format('Y-m-d H:i:s'));
        self::assertSame('2011-03-15 11:15:00', $f->increment($d, true)->format('Y-m-d H:i:s'));
    }

    public function testBadSyntaxesShouldNotValidate(): void
    {
        $f = new MinuteValidator();
        self::assertFalse($f->isValid('*-1'));
        self::assertFalse($f->isValid('1-2-3'));
        self::assertFalse($f->isValid('-1'));
    }
}
