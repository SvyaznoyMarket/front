<?php

return function() {
    $debug = false;

    if (isset($_GET['debug'])) {
        $debug = isset($_GET['debug']) ? (int)$_GET['debug'] : null;
    }

    return $debug;
};