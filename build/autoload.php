<?php

spl_autoload_register(function($class) {
    if (0 === strpos($class, 'Cron\\')) {
        if ('\\' != DIRECTORY_SEPARATOR) {
            $class = 'phar://' . __FILE__ . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
        } else {
            $class = 'phar://' . __FILE__ . DIRECTORY_SEPARATOR . $class . '.php';
        }
        if (file_exists($class)) {
            require $class;
        }
    }
});

__HALT_COMPILER();