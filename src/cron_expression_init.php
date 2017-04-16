<?php
if (defined('CRON_EXPRESSION_INIT')) {
    return;
}

define('CRON_EXPRESSION_INIT', true);

// Load in dependency maps
require dirname(__FILE__).'/Cron/FieldInterface.php';
require dirname(__FILE__).'/Cron/AbstractField.php';
require dirname(__FILE__).'/Cron/DayOfWeekField.php';
require dirname(__FILE__).'/Cron/HoursField.php';
require dirname(__FILE__).'/Cron/YearField.php';
require dirname(__FILE__).'/Cron/CronExpression.php';
require dirname(__FILE__).'/Cron/FieldFactory.php';
require dirname(__FILE__).'/Cron/MinutesField.php';
require dirname(__FILE__).'/Cron/DayOfMonthField.php';
require dirname(__FILE__).'/Cron/MonthField.php';

// Onice Everything is loaded Can use CronExpression as Document
?>