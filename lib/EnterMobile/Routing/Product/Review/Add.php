<?php

namespace EnterMobile\Routing\Product\Review;

use EnterMobile\Routing\Route;

class Add extends Route {

    public function __construct() {
        $this->action = ['Product\\AddReview', 'execute'];
    }
}