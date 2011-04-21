<?php

namespace Cron\Tests;

error_reporting(E_ALL | E_STRICT);

require_once 'PHPUnit/TextUI/TestRunner.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR 
    . 'src' . DIRECTORY_SEPARATOR . 'Cron' . DIRECTORY_SEPARATOR
    . 'CronExpression.php';