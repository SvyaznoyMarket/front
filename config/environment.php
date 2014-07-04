<?php

return function($default = 'dev') {
    return isset($_SERVER['APPLICATION_ENV']) ? $_SERVER['APPLICATION_ENV'] : $default;
};