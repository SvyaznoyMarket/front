<?php

return function() {
    $debugLevel = null;

    if (isset($_GET['APPLICATION_DEBUG'])) {
        $debugLevel = (int)$_GET['APPLICATION_DEBUG'];
    } else if (isset($_COOKIE['debug'])) {
        $debugLevel = (int)$_COOKIE['debug'];
    }

    return $debugLevel;
};