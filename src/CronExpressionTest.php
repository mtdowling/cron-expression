<?php

declare(strict_types=1);

namespace Bakame\Cron;

use DateTime;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

/**
 * @author Michael Dowling <mtdowling@gmail.com>
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
     * @covers \Bakame\Cron\CronExpression::timezone
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
        self::assertEquals(new DateTimeZone(date_default_timezone_get()), $cron->timezone());
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
     * @covers \Bakame\Cron\CronExpression::__construct
     * @covers \Bakame\Cron\CronExpression::setPart
     * @covers \Bakame\Cron\SyntaxError
     */
    public function testInvalidCronsWillFail(): void
    {
        $this->expectException(SyntaxError::class);
        // Only four values
        new CronExpression('* * * 1');
    }

    /**
     * @covers \Bakame\Cron\CronExpression::setPart
     * @covers \Bakame\Cron\SyntaxError
     */
    public function testInvalidPartsWillFail(): void
    {
        $this->expectException(SyntaxError::class);
        // Only four values
        new CronExpression('* * abc * *');
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
     * @covers \Bakame\Cron\CronExpression::match
     * @covers \Bakame\Cron\CronExpression::nextRun
     * @covers \Bakame\Cron\DayOfMonthField
     * @covers \Bakame\Cron\DayOfWeekField
     * @covers \Bakame\Cron\MinutesField
     * @covers \Bakame\Cron\HoursField
     * @covers \Bakame\Cron\MonthField
     * @covers \Bakame\Cron\CronExpression::calculateRun
     * @dataProvider scheduleProvider
     */
    public function testDeterminesIfCronIsDue(string $schedule, string|int $relativeTime, string $nextRun, bool $isDue): void
    {
        // Test next run date
        $cron = new CronExpression($schedule);
        if (is_string($relativeTime)) {
            $relativeTime = new DateTime($relativeTime);
        } else {
            $relativeTime = date('Y-m-d H:i:s', $relativeTime);
        }
        self::assertSame($isDue, $cron->match($relativeTime));
        self::assertEquals(new DateTime($nextRun), $cron->nextRun($relativeTime, 0, CronExpression::ALLOW_CURRENT_DATE));
    }

    /**
     * @covers \Bakame\Cron\CronExpression::match
     */
    public function testIsDueHandlesDifferentDates(): void
    {
        $cron = new CronExpression('* * * * *');
        self::assertTrue($cron->match());
        self::assertTrue($cron->match('now'));
        self::assertTrue($cron->match(new DateTime('now')));
        self::assertTrue($cron->match(date('Y-m-d H:i')));
    }

    /**
     * @covers \Bakame\Cron\CronExpression::match
     */
    public function testIsDueHandlesDifferentTimezones(): void
    {
        $cronUTC = new CronExpression('0 15 * * 3', 'UTC'); //Wednesday at 15:00
        $cronAms = new CronExpression('0 15 * * 3', 'Europe/Amsterdam'); //Wednesday at 15:00
        $cronTok = new CronExpression('0 15 * * 3', 'Asia/Tokyo'); //Wednesday at 15:00
        $date = '2014-01-01 15:00'; //Wednesday
        $utc = new DateTimeZone('UTC');
        $amsterdam =  new DateTimeZone('Europe/Amsterdam');
        $tokyo = new DateTimeZone('Asia/Tokyo');

        date_default_timezone_set('UTC');
        self::assertTrue($cronUTC->match(new DateTime($date, $utc)));
        self::assertFalse($cronUTC->match(new DateTime($date, $amsterdam)));
        self::assertFalse($cronUTC->match(new DateTime($date, $tokyo)));

        date_default_timezone_set('Europe/Amsterdam');
        self::assertFalse($cronAms->match(new DateTime($date, $utc)));
        self::assertTrue($cronAms->match(new DateTime($date, $amsterdam)));
        self::assertFalse($cronAms->match(new DateTime($date, $tokyo)));

        date_default_timezone_set('Asia/Tokyo');
        self::assertFalse($cronTok->match(new DateTime($date, $utc)));
        self::assertFalse($cronTok->match(new DateTime($date, $amsterdam)));
        self::assertTrue($cronTok->match(new DateTime($date, $tokyo)));
    }

    /**
     * @covers Cron\CronExpression::match
     */
    public function testIsDueHandlesDifferentTimezonesAsArgument(): void
    {
        $cronUTC      = new CronExpression('0 15 * * 3', 'UTC'); //Wednesday at 15:00
        $cronAms      = new CronExpression('0 15 * * 3', new DateTimeZone('Europe/Amsterdam')); //Wednesday at 15:00
        $cronTok      = new CronExpression('0 15 * * 3', new DateTimeZone('Asia/Tokyo')); //Wednesday at 15:00
        $date      = '2014-01-01 15:00'; //Wednesday
        $utc       = new DateTimeZone('UTC');
        $amsterdam = new DateTimeZone('Europe/Amsterdam');
        $tokyo     = new DateTimeZone('Asia/Tokyo');
        self::assertTrue($cronUTC->match(new DateTime($date, $utc)));
        self::assertFalse($cronUTC->match(new DateTime($date, $amsterdam)));
        self::assertFalse($cronUTC->match(new DateTime($date, $tokyo)));

        self::assertFalse($cronAms->match(new DateTime($date, $utc)));
        self::assertTrue($cronAms->match(new DateTime($date, $amsterdam)));
        self::assertFalse($cronAms->match(new DateTime($date, $tokyo)));

        self::assertFalse($cronTok->match(new DateTime($date, $utc)));
        self::assertFalse($cronTok->match(new DateTime($date, $amsterdam)));
        self::assertTrue($cronTok->match(new DateTime($date, $tokyo)));
    }

    /**
     * @covers \Bakame\Cron\CronExpression::previousRun
     */
    public function testCanGetPreviousRunDates(): void
    {
        $cron = new CronExpression('* * * * *');
        $next = $cron->nextRun('now');
        $two = $cron->nextRun('now', 1);
        self::assertEquals($next, $cron->previousRun($two));

        $cron = new CronExpression('* */2 * * *');
        $next = $cron->nextRun('now');
        $two = $cron->nextRun('now', 1);
        self::assertEquals($next, $cron->previousRun($two));

        $cron = new CronExpression('* * * */2 *');
        $next = $cron->nextRun('now');
        $two = $cron->nextRun('now', 1);
        self::assertEquals($next, $cron->previousRun($two));
    }

    /**
     * @covers \Bakame\Cron\CronExpression::nextOccurrences
     */
    public function testProvidesMultipleRunDates(): void
    {
        $cron = new CronExpression('*/2 * * * *');
        $result = $cron->nextOccurrences(4, '2008-11-09 00:00:00', CronExpression::ALLOW_CURRENT_DATE);

        self::assertEquals([
            new DateTime('2008-11-09 00:00:00'),
            new DateTime('2008-11-09 00:02:00'),
            new DateTime('2008-11-09 00:04:00'),
            new DateTime('2008-11-09 00:06:00'),
        ], iterator_to_array($result, false));
    }

    /**
     * @covers \Bakame\Cron\CronExpression::nextOccurrences
     * @covers \Bakame\Cron\CronExpression::maxIterationCount
     * @covers \Bakame\Cron\CronExpression::withMaxIterationCount
     */
    public function testProvidesMultipleRunDatesForTheFarFuture(): void
    {
        // Fails with the default 1000 iteration limit
        $cron = new CronExpression('0 0 12 1 *');
        self::assertSame($cron, $cron->withMaxIterationCount($cron->maxIterationCount()));

        $newCron = $cron->withMaxIterationCount(2000);
        self::assertNotEquals($cron, $newCron);

        $result = $newCron->nextOccurrences(9, '2015-04-28 00:00:00', CronExpression::ALLOW_CURRENT_DATE);
        self::assertEquals([
            new DateTime('2016-01-12 00:00:00'),
            new DateTime('2017-01-12 00:00:00'),
            new DateTime('2018-01-12 00:00:00'),
            new DateTime('2019-01-12 00:00:00'),
            new DateTime('2020-01-12 00:00:00'),
            new DateTime('2021-01-12 00:00:00'),
            new DateTime('2022-01-12 00:00:00'),
            new DateTime('2023-01-12 00:00:00'),
            new DateTime('2024-01-12 00:00:00'),
        ], iterator_to_array($result));
    }

    /**
     * @covers \Bakame\Cron\CronExpression
     */
    public function testCanIterateOverNextRuns(): void
    {
        $cron = CronExpression::weekly();
        $nextRun = $cron->nextRun('2008-11-09 08:00:00');
        self::assertEquals($nextRun, new DateTime('2008-11-16 00:00:00'));

        // true is cast to 1
        $nextRun = $cron->nextRun('2008-11-09 00:00:00', 1, CronExpression::ALLOW_CURRENT_DATE);
        self::assertEquals($nextRun, new DateTime('2008-11-16 00:00:00'));

        // You can iterate over them
        $nextRun = $cron->nextRun($cron->nextRun('2008-11-09 00:00:00', 1, CronExpression::ALLOW_CURRENT_DATE), 1, CronExpression::ALLOW_CURRENT_DATE);
        self::assertEquals($nextRun, new DateTime('2008-11-23 00:00:00'));

        // You can skip more than one
        $nextRun = $cron->nextRun('2008-11-09 00:00:00', 2, CronExpression::ALLOW_CURRENT_DATE);
        self::assertEquals($nextRun, new DateTime('2008-11-23 00:00:00'));
        $nextRun = $cron->nextRun('2008-11-09 00:00:00', 3, CronExpression::ALLOW_CURRENT_DATE);
        self::assertEquals($nextRun, new DateTime('2008-11-30 00:00:00'));
    }

    /**
     * @covers \Bakame\Cron\CronExpression::calculateRun
     */
    public function testSkipsCurrentDateByDefault(): void
    {
        $cron = new CronExpression('* * * * *');
        $current = new DateTime('now');
        $next = $cron->nextRun($current);
        $nextPrev = $cron->previousRun($next);
        self::assertSame($current->format('Y-m-d H:i:00'), $nextPrev->format('Y-m-d H:i:s'));
    }

    /**
     * @covers \Bakame\Cron\CronExpression::calculateRun
     * @ticket 7
     */
    public function testStripsForSeconds(): void
    {
        $cron = new CronExpression('* * * * *');
        $current = new DateTime('2011-09-27 10:10:54');
        self::assertSame('2011-09-27 10:11:00', $cron->nextRun($current)->format('Y-m-d H:i:s'));
    }

    /**
     * @covers \Bakame\Cron\CronExpression::calculateRun
     */
    public function testFixesPhpBugInDateIntervalMonth(): void
    {
        $cron = new CronExpression('0 0 27 JAN *');
        self::assertSame('2011-01-27 00:00:00', $cron->previousRun('2011-08-22 00:00:00')->format('Y-m-d H:i:s'));
    }

    public function testIssue29(): void
    {
        $cron = CronExpression::weekly();
        self::assertSame(
            '2013-03-10 00:00:00',
            $cron->previousRun('2013-03-17 00:00:00')->format('Y-m-d H:i:s')
        );
    }

    /**
     * @see https://github.com/mtdowling/cron-expression/issues/20
     */
    public function testIssue20(): void
    {
        $e = new CronExpression('* * * * MON#1');
        self::assertTrue($e->match(new DateTime('2014-04-07 00:00:00')));
        self::assertFalse($e->match(new DateTime('2014-04-14 00:00:00')));
        self::assertFalse($e->match(new DateTime('2014-04-21 00:00:00')));

        $e = new CronExpression('* * * * SAT#2');
        self::assertFalse($e->match(new DateTime('2014-04-05 00:00:00')));
        self::assertTrue($e->match(new DateTime('2014-04-12 00:00:00')));
        self::assertFalse($e->match(new DateTime('2014-04-19 00:00:00')));

        $e = new CronExpression('* * * * SUN#3');
        self::assertFalse($e->match(new DateTime('2014-04-13 00:00:00')));
        self::assertTrue($e->match(new DateTime('2014-04-20 00:00:00')));
        self::assertFalse($e->match(new DateTime('2014-04-27 00:00:00')));
    }

    /**
     * @covers \Bakame\Cron\CronExpression::calculateRun
     */
    public function testKeepOriginalTime(): void
    {
        $now = new DateTime();
        $strNow = $now->format(DateTime::ISO8601);
        $cron = new CronExpression('0 0 * * *');
        $cron->previousRun($now);
        self::assertSame($strNow, $now->format(DateTime::ISO8601));
    }

    /**
     * @covers \Bakame\Cron\CronExpression::__construct
     * @covers \Bakame\Cron\CronExpression::isValid
     * @covers \Bakame\Cron\CronExpression::setPart
     */
    public function testValidationWorks(): void
    {
        // Invalid. Only four values
        self::assertFalse(CronExpression::isValid('* * * 1'));
        // Valid
        self::assertTrue(CronExpression::isValid('* * * * 1'));

        // Issue #156, 13 is an invalid month
        self::assertFalse(CronExpression::isValid('* * * 13 * '));

        // Issue #155, 90 is an invalid second
        self::assertFalse(CronExpression::isValid('90 * * * *'));

        // Issue #154, 24 is an invalid hour
        self::assertFalse(CronExpression::isValid('0 24 1 12 0'));

        // Issue #125, this is just all sorts of wrong
        self::assertFalse(CronExpression::isValid('990 14 * * mon-fri0345345'));
    }

    /**
     * @covers \Bakame\Cron\CronExpression::withMinute
     * @covers \Bakame\Cron\CronExpression::withHour
     * @covers \Bakame\Cron\CronExpression::withDayOfMonth
     * @covers \Bakame\Cron\CronExpression::withMonth
     * @covers \Bakame\Cron\CronExpression::withDayOfWeek
     * @covers \Bakame\Cron\CronExpression::withTimezone
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
        self::assertSame($cron, $cron->withTimezone(date_default_timezone_get()));
    }

    /**
     * @covers \Bakame\Cron\CronExpression::withMinute
     * @covers \Bakame\Cron\CronExpression::withHour
     * @covers \Bakame\Cron\CronExpression::withDayOfMonth
     * @covers \Bakame\Cron\CronExpression::withMonth
     * @covers \Bakame\Cron\CronExpression::withDayOfWeek
     * @covers \Bakame\Cron\CronExpression::withTimezone
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
        self::assertNotEquals($cron, $cron->withTimezone('Africa/Kinshasa'));
    }

    /**
     * @covers \Bakame\Cron\CronExpression::filterDate
     * @covers \Bakame\Cron\SyntaxError
     */
    public function testThrowsIfTheDateCanNotBeInstantiated(): void
    {
        $this->expectException(SyntaxError::class);
        $cron = new CronExpression('23 0-23/2 * * *');
        $cron->nextRun('foobar');
    }
}
