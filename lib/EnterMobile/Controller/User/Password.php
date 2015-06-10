<?php

namespace EnterMobile\Controller\User;

use Enter\Http;

class Password {

    public function execute(Http\Request $request) {

        $response = new Http\Response('empty password');

        return $response;
    }
}