<?php

declare(strict_types=1);

namespace Bakame\Cron;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class SchedulerTest extends TestCase
{
    /**
     * Data provider for cron schedule.
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
            ['0 0 1 * 0', strtotime('2011-06-15 23:09:00'), '2011-06-19 00:00:00', false],
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

            // Issue #7, documented example failed
            ['3-59/15 6-12 */15 1 2-5', strtotime('2017-01-08 00:00:00'), '2017-01-10 06:03:00', false],

            // https://github.com/laravel/framework/commit/07d160ac3cc9764d5b429734ffce4fa311385403
            ['* * * * MON-FRI', strtotime('2017-01-08 00:00:00'), '2017-01-09 00:00:00', false],
            ['* * * * TUE', strtotime('2017-01-08 00:00:00'), '2017-01-10 00:00:00', false],

            // Issue #60, make sure that casing is less relevant for shortcuts, months, and days
            ['0 1 15 JUL mon,Wed,FRi', strtotime('2019-11-14 00:00:00'), '2020-07-01 01:00:00', false],
            ['0 1 15 jul mon,Wed,FRi', strtotime('2019-11-14 00:00:00'), '2020-07-01 01:00:00', false],
            ['@Weekly', strtotime('2019-11-14 00:00:00'), '2019-11-17 00:00:00', false],
            ['@WEEKLY', strtotime('2019-11-14 00:00:00'), '2019-11-17 00:00:00', false],
            ['@WeeklY', strtotime('2019-11-14 00:00:00'), '2019-11-17 00:00:00', false],

            // Issue #76, DOW and DOM do not support ?
            ['0 12 * * ?', strtotime('2020-08-20 00:00:00'), '2020-08-20 12:00:00', false],
            ['0 12 ? * *', strtotime('2020-08-20 00:00:00'), '2020-08-20 12:00:00', false],
        ];
    }

    /**
     * @dataProvider scheduleProvider
     */
    public function testDeterminesIfCronIsDue(string $expression, string|int $relativeTime, string $nextRun, bool $isDue): void
    {
        // Test next run date
        $cron = new Expression($expression);
        if (is_string($relativeTime)) {
            $relativeTime = new DateTime($relativeTime);
        } else {
            $relativeTime = date('Y-m-d H:i:s', $relativeTime);
        }

        $scheduler = Scheduler::fromSystemTimezone($cron);

        self::assertSame($isDue, $scheduler->isDue($relativeTime));
        self::assertEquals(new DateTime($nextRun), $scheduler->includeStartDate()->run(0, $relativeTime));
    }

    public function testIsDueHandlesDifferentDates(): void
    {
        $scheduler = Scheduler::fromSystemTimezone('* * * * *');

        self::assertTrue($scheduler->isDue());
        self::assertTrue($scheduler->isDue('NOW'));
        self::assertTrue($scheduler->isDue(new DateTime('NOW')));
        self::assertTrue($scheduler->isDue(date('Y-m-d H:i')));
    }

    public function testIsDueHandlesDifferentTimezones(): void
    {
        $cron = new Expression('0 15 * * 3'); //Wednesday at 15:00

        $cronUTC = new Scheduler($cron, 'UTC'); //Wednesday at 15:00
        $cronAms = new Scheduler($cron, 'Europe/Amsterdam'); //Wednesday at 15:00
        $cronTok = new Scheduler($cron, new DateTimeZone('Asia/Tokyo')); //Wednesday at 15:00
        $date = '2014-01-01 15:00'; //Wednesday
        $utc = new DateTimeZone('UTC');
        $amsterdam =  new DateTimeZone('Europe/Amsterdam');
        $tokyo = new DateTimeZone('Asia/Tokyo');

        date_default_timezone_set('UTC');
        self::assertTrue($cronUTC->isDue(new DateTime($date, $utc)));
        self::assertFalse($cronUTC->isDue(new DateTime($date, $amsterdam)));
        self::assertFalse($cronUTC->isDue(new DateTime($date, $tokyo)));

        date_default_timezone_set('Europe/Amsterdam');
        self::assertFalse($cronAms->isDue(new DateTime($date, $utc)));
        self::assertTrue($cronAms->isDue(new DateTime($date, $amsterdam)));
        self::assertFalse($cronAms->isDue(new DateTime($date, $tokyo)));

        date_default_timezone_set('Asia/Tokyo');
        self::assertFalse($cronTok->isDue(new DateTime($date, $utc)));
        self::assertFalse($cronTok->isDue(new DateTime($date, $amsterdam)));
        self::assertTrue($cronTok->isDue(new DateTime($date, $tokyo)));
    }

    public function testIsDueHandlesDifferentTimezonesAsArgument(): void
    {
        $cron = new Expression('0 15 * * 3');
        $cronUTC = new Scheduler($cron, 'UTC'); //Wednesday at 15:00
        $cronAms = new Scheduler($cron, new DateTimeZone('Europe/Amsterdam')); //Wednesday at 15:00
        $cronTok = new Scheduler($cron, new DateTimeZone('Asia/Tokyo')); //Wednesday at 15:00
        $date = '2014-01-01 15:00'; //Wednesday
        $utc = new DateTimeZone('UTC');
        $amsterdam = new DateTimeZone('Europe/Amsterdam');
        $tokyo = new DateTimeZone('Asia/Tokyo');
        self::assertTrue($cronUTC->isDue(new DateTime($date, $utc)));
        self::assertFalse($cronUTC->isDue(new DateTime($date, $amsterdam)));
        self::assertFalse($cronUTC->isDue(new DateTime($date, $tokyo)));

        self::assertFalse($cronAms->isDue(new DateTime($date, $utc)));
        self::assertTrue($cronAms->isDue(new DateTime($date, $amsterdam)));
        self::assertFalse($cronAms->isDue(new DateTime($date, $tokyo)));

        self::assertFalse($cronTok->isDue(new DateTime($date, $utc)));
        self::assertFalse($cronTok->isDue(new DateTime($date, $amsterdam)));
        self::assertTrue($cronTok->isDue(new DateTime($date, $tokyo)));
    }

    public function testCanGetPreviousRunDates(): void
    {
        $scheduler = Scheduler::fromSystemTimezone('* * * * *');
        $next = $scheduler->run();
        $two = $scheduler->run(1);
        self::assertEquals($next, $scheduler->run(-1, $two));

        $cron = Scheduler::fromSystemTimezone(new Expression('* */2 * * *'));
        $next = $cron->run();
        $two = $cron->run(1);
        self::assertEquals($next, $cron->run(-1, $two));

        $cron = Scheduler::fromSystemTimezone('* * * */2 *');
        $next = $cron->run();
        $two = $cron->run(1);
        self::assertEquals($next, $cron->run(-1, $two));
    }

    public function testProvidesMultipleRunDates(): void
    {
        $cron = Scheduler::fromSystemTimezone(new Expression('*/2 * * * *'))->includeStartDate();
        $result = $cron->yieldRunsForward(4, '2008-11-09 00:00:00');

        self::assertEquals([
            new DateTime('2008-11-09 00:00:00'),
            new DateTime('2008-11-09 00:02:00'),
            new DateTime('2008-11-09 00:04:00'),
            new DateTime('2008-11-09 00:06:00'),
        ], iterator_to_array($result, false));
    }

    public function testProvidesMultipleRunDatesForTheFarFuture(): void
    {
        // Fails with the default 1000 iteration limit
        $cron = Scheduler::fromSystemTimezone(new Expression('0 0 12 1 *'));
        $cron = $cron->includeStartDate();
        self::assertSame($cron, $cron->withMaxIterationCount($cron->maxIterationCount()));

        $newCron = $cron->withMaxIterationCount(2000);
        self::assertNotEquals($cron, $newCron);

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
        ], iterator_to_array($newCron->yieldRunsForward(9, '2015-04-28 00:00:00')));
    }

    public function testCanIterateOverNextRuns(): void
    {
        $cron = Scheduler::fromSystemTimezone(Expression::weekly());
        $nextRun = $cron->run(startDate:'2008-11-09 08:00:00');
        self::assertEquals($nextRun, new DateTime('2008-11-16 00:00:00'));

        // true is cast to 1
        $nextRun = $cron->includeStartDate()->run(1, '2008-11-09 00:00:00');
        self::assertEquals($nextRun, new DateTime('2008-11-16 00:00:00'));

        // You can iterate over them
        $nextRun = $cron->includeStartDate()->run(1, $cron->includeStartDate()->run(1, '2008-11-09 00:00:00'));
        self::assertEquals($nextRun, new DateTime('2008-11-23 00:00:00'));

        // You can skip more than one
        $nextRun = $cron->includeStartDate()->run(2, '2008-11-09 00:00:00');
        self::assertEquals($nextRun, new DateTime('2008-11-23 00:00:00'));
        $nextRun = $cron->includeStartDate()->run(3, '2008-11-09 00:00:00');
        self::assertEquals($nextRun, new DateTime('2008-11-30 00:00:00'));
    }

    public function testSkipsCurrentDateByDefault(): void
    {
        $cron = Scheduler::fromSystemTimezone(new Expression('* * * * *'));
        $current = new DateTime('now');
        $next = $cron->run(0, $current);
        $nextPrev = $cron->run(-1, $next);
        self::assertSame($current->format('Y-m-d H:i:00'), $nextPrev->format('Y-m-d H:i:s'));
    }

    public function testStripsForSeconds(): void
    {
        $cron = Scheduler::fromSystemTimezone(new Expression('* * * * *'));
        $current = new DateTime('2011-09-27 10:10:54');
        self::assertSame('2011-09-27 10:11:00', $cron->run(0, $current)->format('Y-m-d H:i:s'));
    }

    public function testFixesPhpBugInDateIntervalMonth(): void
    {
        $cron = Scheduler::fromSystemTimezone(new Expression('0 0 27 JAN *'));
        self::assertSame('2011-01-27 00:00:00', $cron->run(-1, '2011-08-22 00:00:00')->format('Y-m-d H:i:s'));
    }

    public function testIssue29(): void
    {
        $cron = Scheduler::fromSystemTimezone(Expression::weekly());
        self::assertSame(
            '2013-03-10 00:00:00',
            $cron->run(-1, '2013-03-17 00:00:00')->format('Y-m-d H:i:s')
        );
    }

    public function testIssue20(): void
    {
        $scheduler = Scheduler::fromSystemTimezone(new Expression('* * * * MON#1'));
        self::assertTrue($scheduler->isDue(new DateTime('2014-04-07 00:00:00')));
        self::assertFalse($scheduler->isDue(new DateTime('2014-04-14 00:00:00')));
        self::assertFalse($scheduler->isDue(new DateTime('2014-04-21 00:00:00')));

        $scheduler = Scheduler::fromSystemTimezone(new Expression('* * * * SAT#2'));
        self::assertFalse($scheduler->isDue(new DateTime('2014-04-05 00:00:00')));
        self::assertTrue($scheduler->isDue(new DateTime('2014-04-12 00:00:00')));
        self::assertFalse($scheduler->isDue(new DateTime('2014-04-19 00:00:00')));

        $scheduler = Scheduler::fromSystemTimezone(new Expression('* * * * SUN#3'));
        self::assertFalse($scheduler->isDue(new DateTime('2014-04-13 00:00:00')));
        self::assertTrue($scheduler->isDue(new DateTime('2014-04-20 00:00:00')));
        self::assertFalse($scheduler->isDue(new DateTime('2014-04-27 00:00:00')));
    }

    public function testKeepOriginalTime(): void
    {
        $now = new DateTime();
        $strNow = $now->format(DateTime::ISO8601);
        $cron = Scheduler::fromSystemTimezone(new Expression('0 0 * * *'));
        $cron->run(-1, $now);
        self::assertSame($strNow, $now->format(DateTime::ISO8601));
    }

    public function testUpdateCronExpressionPartReturnsTheSameInstance(): void
    {
        $cron = Scheduler::fromSystemTimezone(new Expression('23 0-23/2 * * *'));

        self::assertSame($cron, $cron->withTimezone(date_default_timezone_get()));
    }

    public function testUpdateCronExpressionPartReturnsADifferentInstance(): void
    {
        $cron = Scheduler::fromSystemTimezone(new Expression('23 0-23/2 * * *'));

        self::assertNotEquals($cron, $cron->withTimezone('Africa/Kinshasa'));
    }

    public function testThrowsIfTheDateCanNotBeInstantiated(): void
    {
        $this->expectException(SyntaxError::class);
        $cron = Scheduler::fromSystemTimezone(new Expression('23 0-23/2 * * *'));
        $cron->run(0, 'foobar');
    }

    public function testThrowsIfMaxIterationCountIsNegative(): void
    {
        $this->expectException(SyntaxError::class);

        new Scheduler(new Expression('* * * * *'), 'Africa/Nairobi', Scheduler::EXCLUDE_START_DATE, -1);
    }

    /**
     * Makes sure that 00 is considered a valid value for 0-based fields
     * cronie allows numbers with a leading 0, so adding support for this as well.
     *
     * @see https://github.com/dragonmantank/cron-expression/issues/12
     */
    public function testDoubleZeroIsValid(): void
    {
        $scheduler = Scheduler::fromSystemTimezone(new Expression('00 * * * *'));
        self::assertTrue($scheduler->isDue(new DateTime('2014-04-07 00:00:00')));

        $scheduler = Scheduler::fromSystemTimezone(new Expression('01 * * * *'));
        self::assertTrue($scheduler->isDue(new DateTime('2014-04-07 00:01:00')));

        $scheduler = Scheduler::fromSystemTimezone(new Expression('* 00 * * *'));
        self::assertTrue($scheduler->isDue(new DateTime('2014-04-07 00:00:00')));

        $scheduler = Scheduler::fromSystemTimezone(new Expression('* 01 * * *'));
        self::assertTrue($scheduler->isDue(new DateTime('2014-04-07 01:00:00')));
    }

    /**
     * Ranges with large steps should "wrap around" to the appropriate value
     * cronie allows for steps that are larger than the range of a field, with it wrapping around like a ring buffer. We
     * should do the same.
     *
     * @see https://github.com/dragonmantank/cron-expression/issues/6
     */
    public function testRangesWrapAroundWithLargeSteps(): void
    {
        $scheduler = Scheduler::fromSystemTimezone(new Expression('* * * */123 *'));
        self::assertTrue($scheduler->isDue(new DateTime('2014-04-07 00:00:00')));

        $nextRunDate = $scheduler->run(startDate: new DateTime('2014-04-07 00:00:00'));
        self::assertSame('2014-04-07 00:01:00', $nextRunDate->format('Y-m-d H:i:s'));

        $nextRunDate = $scheduler->run(startDate: new DateTime('2014-05-07 00:00:00'));
        self::assertSame('2015-04-01 00:00:00', $nextRunDate->format('Y-m-d H:i:s'));
    }

    /**
     * @see https://github.com/dragonmantank/cron-expression/issues/35
     */
    public function testMakeDayOfWeekAnOrSometimes(): void
    {
        $cron = Scheduler::fromSystemTimezone(new Expression('30 0 1 * 1'))->includeStartDate();
        $runs = $cron->yieldRunsForward(5, new DateTime('2019-10-10 23:20:00'));
        $runs = iterator_to_array($runs, false);
        self::assertSame('2019-10-14 00:30:00', $runs[0]->format('Y-m-d H:i:s'));
        self::assertSame('2019-10-21 00:30:00', $runs[1]->format('Y-m-d H:i:s'));
        self::assertSame('2019-10-28 00:30:00', $runs[2]->format('Y-m-d H:i:s'));
        self::assertSame('2019-11-01 00:30:00', $runs[3]->format('Y-m-d H:i:s'));
        self::assertSame('2019-11-04 00:30:00', $runs[4]->format('Y-m-d H:i:s'));
    }

    public function testRecognisesTimezonesAsPartOfDateTime(): void
    {
        $tzCron = 'America/New_York';
        $tzServer = new DateTimeZone('Europe/London');
        $scheduler = new Scheduler(expression: '0 7 * * *', timezone: $tzCron, startDatePresence: Scheduler::EXCLUDE_START_DATE);

        /** @var DateTime $dtCurrent */
        $dtCurrent = DateTime::createFromFormat('!Y-m-d H:i:s', '2017-10-17 10:00:00', $tzServer);
        $dtPrev = $scheduler->run(-1, $dtCurrent);
        self::assertEquals('1508151600 : 2017-10-16T07:00:00-04:00 : America/New_York', $dtPrev->format('U \\: c \\: e'));

        /** @var DateTimeImmutable $dtCurrent */
        $dtCurrent = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', '2017-10-17 10:00:00', $tzServer);
        $dtPrev = $scheduler->run(-1, $dtCurrent);
        self::assertEquals('1508151600 : 2017-10-16T07:00:00-04:00 : America/New_York', $dtPrev->format('U \\: c \\: e'));

        /** @var DateTimeImmutable $dtCurrent */
        $dtCurrent = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', '2017-10-17 10:00:00', $tzServer);
        $dtPrev = $scheduler->run(-1, $dtCurrent->format('c'));
        self::assertEquals('1508151600 : 2017-10-16T07:00:00-04:00 : America/New_York', $dtPrev->format('U \\: c \\: e'));

        /** @var DateTimeImmutable $dtCurrent */
        $dtCurrent = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', '2017-10-17 10:00:00', $tzServer);
        $dtPrev = $scheduler->run(-1, $dtCurrent->format('\\@U'));
        self::assertEquals('1508151600 : 2017-10-16T07:00:00-04:00 : America/New_York', $dtPrev->format('U \\: c \\: e'));
    }

    public function testChangingTheSchedulerProperties(): void
    {
        $initial = new Scheduler('0 7 * * *', 'Africa/Nairobi', Scheduler::EXCLUDE_START_DATE, 2000);

        self::assertSame($initial, $initial->withExpression($initial->expression()));
        self::assertSame($initial, $initial->withTimezone($initial->timezone()));
        self::assertSame($initial, $initial->excludeStartDate());
        self::assertSame($initial, $initial->withMaxIterationCount($initial->maxIterationCount()));
        self::assertTrue($initial->isStartDateExcluded());

        $includeStartDate = $initial->includeStartDate();

        self::assertNotEquals($initial, $initial->withExpression(new Expression('0 5 * * *')));
        self::assertNotEquals($initial, $initial->withTimezone(new DateTimeZone('Africa/Dakar')));
        self::assertNotEquals($initial, $includeStartDate);
        self::assertNotEquals($initial, $initial->withMaxIterationCount(1000));
        self::assertFalse($includeStartDate->isStartDateExcluded());
        self::assertSame($includeStartDate, $includeStartDate->includeStartDate());
        self::assertEquals($initial, $includeStartDate->excludeStartDate());
    }

    public function testInvalidStartDatePresence(): void
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage(SyntaxError::dueToInvalidStartDatePresence()->getMessage());

        new Scheduler('0 7 * * *', 'Africa/Nairobi', 3);
    }

    public function testIsDueReturnsFalseWithBrokenInput(): void
    {
        $scheduler = new Scheduler('0 7 * * *', 'Africa/Nairobi', Scheduler::EXCLUDE_START_DATE, 2000);

        self::assertFalse($scheduler->isDue('foobar'));
    }

    public function testNewUTCInstance(): void
    {
        $scheduler = Scheduler::fromUTC('0 7 * * *');

        self::assertEquals('0 7 * * *', $scheduler->expression()->toString());
        self::assertEquals('UTC', $scheduler->timezone()->getName());
        self::assertEquals(1000, $scheduler->maxIterationCount());
        self::assertTrue($scheduler->isStartDateExcluded());
    }

    public function testNewCurrentTimezoneInstance(): void
    {
        $currentTimezone = date_default_timezone_get();
        date_default_timezone_set('Africa/Lagos');
        $scheduler = Scheduler::fromSystemTimezone('0 7 * * *');

        self::assertEquals('0 7 * * *', $scheduler->expression()->toString());
        self::assertEquals('Africa/Lagos', $scheduler->timezone()->getName());
        self::assertEquals(1000, $scheduler->maxIterationCount());
        self::assertTrue($scheduler->isStartDateExcluded());

        date_default_timezone_set($currentTimezone);
    }
}
