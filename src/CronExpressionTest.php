<?php

declare(strict_types=1);

namespace Bakame\Cron;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Bakame\Cron\CronExpression
 */
final class CronExpressionTest extends TestCase
{
    /**
     * @covers \Bakame\Cron\CronExpression::yearly
     * @covers \Bakame\Cron\CronExpression::monthly
     * @covers \Bakame\Cron\CronExpression::weekly
     * @covers \Bakame\Cron\CronExpression::daily
     * @covers \Bakame\Cron\CronExpression::hourly
     */
    public function testFactoryRecognizesTemplates(): void
    {
        self::assertSame('0 0 1 1 *', CronExpression::yearly()->toString());
        self::assertSame('0 0 1 * *', CronExpression::monthly()->toString());
        self::assertSame('0 0 * * 0', CronExpression::weekly()->toString());
        self::assertSame('0 0 * * *', CronExpression::daily()->toString());
        self::assertSame('0 * * * *', CronExpression::hourly()->toString());
    }

    /**
     * @covers \Bakame\Cron\CronExpression::__construct
     * @covers \Bakame\Cron\CronExpression::toString
     * @covers \Bakame\Cron\CronExpression::fields
     * @covers \Bakame\Cron\CronExpression::jsonSerialize
     * @covers \Bakame\Cron\CronExpression::__toString
     */
    public function testParsesCronSchedule(): void
    {
        // '2010-09-10 12:00:00'
        $cron = new CronExpression('1 2-4 * 4,5,6 */3');
        self::assertSame('1', $cron->minute());
        self::assertSame('2-4', $cron->hour());
        self::assertSame('*', $cron->dayOfMonth());
        self::assertSame('4,5,6', $cron->month());
        self::assertSame('*/3', $cron->dayOfWeek());
        self::assertSame('1 2-4 * 4,5,6 */3', $cron->toString());
        self::assertSame('1 2-4 * 4,5,6 */3', (string) $cron);
        self::assertSame(['1', '2-4', '*', '4,5,6', '*/3'], $cron->fields());
        self::assertSame('"1 2-4 * 4,5,6 *\/3"', json_encode($cron));
    }

    /**
     * @covers \Bakame\Cron\CronExpression::__construct
     * @covers \Bakame\Cron\SyntaxError
     */
    public function testParsesCronScheduleThrowsAnException(): void
    {
        $this->expectException(SyntaxError::class);

        new CronExpression('A 1 2 3 4');
    }

    /**
     * @covers \Bakame\Cron\CronExpression::__construct
     * @covers \Bakame\Cron\CronExpression::minute
     * @covers \Bakame\Cron\CronExpression::hour
     * @covers \Bakame\Cron\CronExpression::dayOfMonth
     * @covers \Bakame\Cron\CronExpression::month
     * @covers \Bakame\Cron\CronExpression::dayOfWeek
     * @dataProvider scheduleWithDifferentSeparatorsProvider
     */
    public function testParsesCronScheduleWithAnySpaceCharsAsSeparators(string $schedule, array $expected): void
    {
        $cron = new CronExpression($schedule);
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

    /**
     * Data provider for cron schedule.
     *
     */
    public function scheduleProvider(): array
    {
        return [
            ['*/2 */2 * * *', '2015-08-10 21:47:27', '2015-08-10 22:00:00', false],
            ['* * * * *', '2015-08-10 21:50:37', '2015-08-10 21:50:00', true],
            ['* 20,21,22 * * *', '2015-08-10 21:50:00', '2015-08-10 21:50:00', true],
            // Handles CSV values
            ['* 20,22 * * *', '2015-08-10 21:50:00', '2015-08-10 22:00:00', false],
            // CSV values can be complex
            ['7-9 * */9 * *', '2015-08-10 22:02:33', '2015-08-10 22:07:00', false],
            // 15th minute, of the second hour, every 15 days, in January, every Friday
            ['1 * * * 7', '2015-08-10 21:47:27', '2015-08-16 00:01:00', false],
            // Test with exact times
            ['47 21 * * *', strtotime('2015-08-10 21:47:30'), '2015-08-10 21:47:00', true],
            // Test Day of the week (issue #1)
            // According cron implementation, 0|7 = sunday, 1 => monday, etc
            ['* * * * 0', strtotime('2011-06-15 23:09:00'), '2011-06-19 00:00:00', false],
            ['* * * * 7', strtotime('2011-06-15 23:09:00'), '2011-06-19 00:00:00', false],
            ['* * * * 1', strtotime('2011-06-15 23:09:00'), '2011-06-20 00:00:00', false],
            // Should return the sunday date as 7 equals 0
            ['0 0 * * MON,SUN', strtotime('2011-06-15 23:09:00'), '2011-06-19 00:00:00', false],
            ['0 0 * * 1,7', strtotime('2011-06-15 23:09:00'), '2011-06-19 00:00:00', false],
            ['0 0 * * 0-4', strtotime('2011-06-15 23:09:00'), '2011-06-16 00:00:00', false],
            ['0 0 * * 7-4', strtotime('2011-06-15 23:09:00'), '2011-06-16 00:00:00', false],
            ['0 0 * * 4-7', strtotime('2011-06-15 23:09:00'), '2011-06-16 00:00:00', false],
            ['0 0 * * 7-3', strtotime('2011-06-15 23:09:00'), '2011-06-19 00:00:00', false],
            ['0 0 * * 3-7', strtotime('2011-06-15 23:09:00'), '2011-06-16 00:00:00', false],
            ['0 0 * * 3-7', strtotime('2011-06-18 23:09:00'), '2011-06-19 00:00:00', false],
            // Test lists of values and ranges (Abhoryo)
            ['0 0 * * 2-7', strtotime('2011-06-20 23:09:00'), '2011-06-21 00:00:00', false],
            ['0 0 * * 2-7', strtotime('2011-06-18 23:09:00'), '2011-06-19 00:00:00', false],
            ['0 0 * * 4-7', strtotime('2011-07-19 00:00:00'), '2011-07-21 00:00:00', false],
            // Test increments of ranges
            ['0-12/4 * * * *', strtotime('2011-06-20 12:04:00'), '2011-06-20 12:04:00', true],
            ['4-59/2 * * * *', strtotime('2011-06-20 12:04:00'), '2011-06-20 12:04:00', true],
            ['4-59/2 * * * *', strtotime('2011-06-20 12:06:00'), '2011-06-20 12:06:00', true],
            ['4-59/3 * * * *', strtotime('2011-06-20 12:06:00'), '2011-06-20 12:07:00', false],
            // Test Day of the Week and the Day of the Month (issue #1)
            ['0 0 1 1 0', strtotime('2011-06-15 23:09:00'), '2012-01-01 00:00:00', false],
            ['0 0 1 JAN 0', strtotime('2011-06-15 23:09:00'), '2012-01-01 00:00:00', false],
            ['0 0 1 * 0', strtotime('2011-06-15 23:09:00'), '2012-01-01 00:00:00', false],
            // Test the W day of the week modifier for day of the month field
            ['0 0 2W * *', strtotime('2011-07-01 00:00:00'), '2011-07-01 00:00:00', true],
            ['0 0 1W * *', strtotime('2011-05-01 00:00:00'), '2011-05-02 00:00:00', false],
            ['0 0 1W * *', strtotime('2011-07-01 00:00:00'), '2011-07-01 00:00:00', true],
            ['0 0 3W * *', strtotime('2011-07-01 00:00:00'), '2011-07-04 00:00:00', false],
            ['0 0 16W * *', strtotime('2011-07-01 00:00:00'), '2011-07-15 00:00:00', false],
            ['0 0 28W * *', strtotime('2011-07-01 00:00:00'), '2011-07-28 00:00:00', false],
            ['0 0 30W * *', strtotime('2011-07-01 00:00:00'), '2011-07-29 00:00:00', false],
            ['0 0 31W * *', strtotime('2011-07-01 00:00:00'), '2011-07-29 00:00:00', false],
            // Test the last weekday of a month
            ['* * * * 5L', strtotime('2011-07-01 00:00:00'), '2011-07-29 00:00:00', false],
            ['* * * * 6L', strtotime('2011-07-01 00:00:00'), '2011-07-30 00:00:00', false],
            ['* * * * 7L', strtotime('2011-07-01 00:00:00'), '2011-07-31 00:00:00', false],
            ['* * * * 1L', strtotime('2011-07-24 00:00:00'), '2011-07-25 00:00:00', false],
            ['* * * 1 5L', strtotime('2011-12-25 00:00:00'), '2012-01-27 00:00:00', false],
            // Test the hash symbol for the nth weekday of a given month
            ['* * * * 5#2', strtotime('2011-07-01 00:00:00'), '2011-07-08 00:00:00', false],
            ['* * * * 5#1', strtotime('2011-07-01 00:00:00'), '2011-07-01 00:00:00', true],
            ['* * * * 3#4', strtotime('2011-07-01 00:00:00'), '2011-07-27 00:00:00', false],
        ];
    }

    /**
     * @covers \Bakame\Cron\CronExpression::withMinute
     * @covers \Bakame\Cron\CronExpression::withHour
     * @covers \Bakame\Cron\CronExpression::withDayOfMonth
     * @covers \Bakame\Cron\CronExpression::withMonth
     * @covers \Bakame\Cron\CronExpression::withDayOfWeek
     * @covers \Bakame\Cron\CronExpression::newInstance
     */
    public function testUpdateCronExpressionPartReturnsTheSameInstance(): void
    {
        $cron = new CronExpression('23 0-23/2 * * *');

        self::assertSame($cron, $cron->withMinute($cron->minute()));
        self::assertSame($cron, $cron->withHour($cron->hour()));
        self::assertSame($cron, $cron->withMonth($cron->month()));
        self::assertSame($cron, $cron->withDayOfMonth($cron->dayOfMonth()));
        self::assertSame($cron, $cron->withDayOfWeek($cron->dayOfWeek()));
    }

    /**
     * @covers \Bakame\Cron\CronExpression::withMinute
     * @covers \Bakame\Cron\CronExpression::withHour
     * @covers \Bakame\Cron\CronExpression::withDayOfMonth
     * @covers \Bakame\Cron\CronExpression::withMonth
     * @covers \Bakame\Cron\CronExpression::withDayOfWeek
     * @covers \Bakame\Cron\CronExpression::newInstance
     */
    public function testUpdateCronExpressionPartReturnsADifferentInstance(): void
    {
        $cron = new CronExpression('23 0-23/2 * * *');

        self::assertNotEquals($cron, $cron->withMinute('22'));
        self::assertNotEquals($cron, $cron->withHour('12'));
        self::assertNotEquals($cron, $cron->withDayOfMonth('28'));
        self::assertNotEquals($cron, $cron->withMonth('12'));
        self::assertNotEquals($cron, $cron->withDayOfWeek('Fri'));
    }
}
