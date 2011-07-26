<?php

namespace Cron\Tests;

error_reporting(E_ALL | E_STRICT);

require_once 'PHPUnit/TextUI/TestRunner.php';

$prefix = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
    . 'src' . DIRECTORY_SEPARATOR . 'Cron' . DIRECTORY_SEPARATOR;

foreach (array(
    'FieldFactory.php',
    'FieldInterface.php',
    'AbstractField.php',
    'CronExpression.php',
    'DayOfMonthField.php',
    'DayOfWeekField.php',
    'HoursField.php',
    'MinutesField.php',
    'MonthField.php',
    'YearField.php'
) as $class) {
    require_once $prefix . $class;
}