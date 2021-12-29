<?php

declare(strict_types=1);

namespace Bakame\Cron;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Bakame\Cron\Expression
 */
final class ExpressionTest extends TestCase
{
    public function testFactoryRecognizesTemplates(): void
    {
        self::assertSame('0 0 1 1 *', Expression::yearly()->toString());
        self::assertSame('0 0 1 * *', Expression::monthly()->toString());
        self::assertSame('0 0 * * 0', Expression::weekly()->toString());
        self::assertSame('0 0 * * *', Expression::daily()->toString());
        self::assertSame('0 * * * *', Expression::hourly()->toString());
    }

    public function testParsesCronSchedule(): void
    {
        // '2010-09-10 12:00:00'
        $cron = new Expression('1 2-4 * 4,5,6 */3');
        self::assertSame('1', $cron->minute());
        self::assertSame('2-4', $cron->hour());
        self::assertSame('*', $cron->dayOfMonth());
        self::assertSame('4,5,6', $cron->month());
        self::assertSame('*/3', $cron->dayOfWeek());
        self::assertSame('1 2-4 * 4,5,6 */3', $cron->toString());
        self::assertSame('1 2-4 * 4,5,6 */3', (string) $cron);
        self::assertSame(['1', '2-4', '*', '4,5,6', '*/3'], array_values($cron->fields()));
        self::assertSame('"1 2-4 * 4,5,6 *\/3"', json_encode($cron));
    }

    public function testParsesCronScheduleThrowsAnException(): void
    {
        $this->expectException(SyntaxError::class);

        new Expression('A 1 2 3 4');
    }

    /**
     * @dataProvider scheduleWithDifferentSeparatorsProvider
     */
    public function testParsesCronScheduleWithAnySpaceCharsAsSeparators(string $schedule, array $expected): void
    {
        $cron = new Expression($schedule);

        self::assertSame($expected[0], $cron->minute());
        self::assertSame($expected[1], $cron->hour());
        self::assertSame($expected[2], $cron->dayOfMonth());
        self::assertSame($expected[3], $cron->month());
        self::assertSame($expected[4], $cron->dayOfWeek());
    }

    /**
     * Data provider for testParsesCronScheduleWithAnySpaceCharsAsSeparators.
     */
    public static function scheduleWithDifferentSeparatorsProvider(): array
    {
        return [
            ["*\t*\t*\t*\t*\t", ['*', '*', '*', '*', '*', '*']],
            ['*  *  *  *  *  ', ['*', '*', '*', '*', '*', '*']],
            ["* \t * \t * \t * \t * \t", ['*', '*', '*', '*', '*', '*']],
            ["*\t \t*\t \t*\t \t*\t \t*\t \t", ['*', '*', '*', '*', '*', '*']],
        ];
    }

    public function testUpdateCronExpressionPartReturnsTheSameInstance(): void
    {
        $cron = new Expression('23 0-23/2 * * *');

        self::assertSame($cron, $cron->withMinute($cron->minute()));
        self::assertSame($cron, $cron->withHour($cron->hour()));
        self::assertSame($cron, $cron->withMonth($cron->month()));
        self::assertSame($cron, $cron->withDayOfMonth($cron->dayOfMonth()));
        self::assertSame($cron, $cron->withDayOfWeek($cron->dayOfWeek()));
    }

    public function testUpdateCronExpressionPartReturnsADifferentInstance(): void
    {
        $cron = new Expression('23 0-23/2 * * *');

        self::assertNotEquals($cron, $cron->withMinute('22'));
        self::assertNotEquals($cron, $cron->withHour('12'));
        self::assertNotEquals($cron, $cron->withDayOfMonth('28'));
        self::assertNotEquals($cron, $cron->withMonth('12'));
        self::assertNotEquals($cron, $cron->withDayOfWeek('Fri'));
    }

    public function testInvalidPartsWillFail(): void
    {
        $this->expectException(SyntaxError::class);

        (new Expression('* * * * *'))->withDayOfWeek('abc');
    }

    public function testInstantiationFromFieldsList(): void
    {
        self::assertSame('* * * * *', Expression::fromFields([])->toString());
        self::assertSame('7 * * * 5', Expression::fromFields(['minute' => 7, 'dayOfWeek' => '5'])->toString());
    }

    public function testInstantiationFromFieldsListWillFail(): void
    {
        $this->expectException(SyntaxError::class);

        Expression::fromFields(['foo' => 'bar', 'minute' => '23']);
    }

    public function testExpressionInternalPhpMethod(): void
    {
        $cronOriginal = new Expression('5 4 3 2 1');
        /** @var Expression $cron */
        $cron = eval('return '.var_export($cronOriginal, true).';');

        self::assertEquals($cronOriginal, $cron);
    }
}
