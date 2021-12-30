<?php

declare(strict_types=1);

namespace Bakame\Cron;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Bakame\Cron\FieldValidator
 */
final class FieldValidatorTest extends TestCase
{
    public function testAllowRangesAndLists(): void
    {
        $expression = '5-7,11-13';
        $f = new HourValidator();
        self::assertTrue($f->isValid($expression));
    }
}
