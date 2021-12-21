<?php

declare(strict_types=1);

namespace Cron;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @author Michael Dowling <mtdowling@gmail.com>
 */
class FieldFactoryTest extends TestCase
{
    /**
     * @covers \Cron\FieldFactory::getField
     */
    public function testRetrievesFieldInstances(): void
    {
        $mappings = [
            0 => 'MinutesField',
            1 => 'HoursField',
            2 => 'DayOfMonthField',
            3 => 'MonthField',
            4 => 'DayOfWeekField',
        ];

        $f = new FieldFactory();

        foreach ($mappings as $position => $class) {
            self::assertSame('Cron\\'.$class, get_class($f->getField($position)));
        }
    }

    /**
     * @covers \Cron\FieldFactory::getField
     */
    public function testValidatesFieldPosition(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $f = new FieldFactory();
        $f->getField(-1);
    }
}
