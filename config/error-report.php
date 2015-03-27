<?php

return function() {
    ini_set('log_errors', true);
    error_reporting(-1);
    //ini_set('error_log', $applicationDir . '/log/php-error.log');
    //ini_set('ignore_repeated_source', false);
    //ini_set('ignore_repeated_errors', true);

    ini_set('display_errors', false);
};