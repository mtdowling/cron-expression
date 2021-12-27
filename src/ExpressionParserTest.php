<?php

declare(strict_types=1);

namespace Bakame\Cron;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Bakame\Cron\ExpressionParser
 */
final class ExpressionParserTest extends TestCase
{
    public function testValidationWorks(): void
    {
        // Invalid. Only four values
        self::assertFalse(ExpressionParser::isValid('* * * 1'));
        // Valid
        self::assertTrue(ExpressionParser::isValid('* * * * 1'));

        // Issue #156, 13 is an invalid month
        self::assertFalse(ExpressionParser::isValid('* * * 13 * '));

        // Issue #155, 90 is an invalid second
        self::assertFalse(ExpressionParser::isValid('90 * * * *'));

        // Issue #154, 24 is an invalid hour
        self::assertFalse(ExpressionParser::isValid('0 24 1 12 0'));

        // Issue #125, this is just all sorts of wrong
        self::assertFalse(ExpressionParser::isValid('990 14 * * mon-fri0345345'));
    }

    /**
     * @covers \Bakame\Cron\SyntaxError
     */
    public function testInvalidCronsWillFail(): void
    {
        $this->expectException(SyntaxError::class);

        ExpressionParser::parse('* * * 1');
    }


    /**
     * @covers \Bakame\Cron\SyntaxError
     */
    public function testInvalidPartsWillFail(): void
    {
        $this->expectException(SyntaxError::class);

        ExpressionParser::parse('* * abc * *');
    }

    /**
     * Makes sure that 00 is considered a valid value for 0-based fields
     * cronie allows numbers with a leading 0, so adding support for this as well.
     *
     * @see https://github.com/dragonmantank/cron-expression/issues/12
     */
    public function testDoubleZeroIsValid(): void
    {
        self::assertTrue(ExpressionParser::isValid('00 * * * *'));
        self::assertTrue(ExpressionParser::isValid('01 * * * *'));
        self::assertTrue(ExpressionParser::isValid('* 00 * * *'));
        self::assertTrue(ExpressionParser::isValid('* 01 * * *'));
    }
}
