<?php

namespace EnterMobile\Controller\User;

use Enter\Http;

class Orders {

    public function execute(Http\Request $request) {

        $response = new Http\Response('empty orders');

        return $response;
    }
}