<?php

return function($applicationDir) {
    set_include_path(get_include_path() . PATH_SEPARATOR . implode(PATH_SEPARATOR, [
        realpath($applicationDir . '/lib'),
    ]));

    spl_autoload_register(function ($class) {
        if ($class[0] === '\\') {
            $class = substr($class, 1);
        }

        if (
            (0 !== strpos($class, 'Enter'))
            && (0 !== strpos($class, 'EnterMobile'))
            && (0 !== strpos($class, 'EnterTerminal')) // FIXME: вынести в отдельный автозагрузчик
        ) {
            return;
        }

        //echo str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php' . PHP_EOL;

        include_once str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    });
};