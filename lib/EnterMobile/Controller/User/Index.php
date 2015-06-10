<?php

namespace EnterMobile\Controller\User;

use Enter\Http;

class Index {

    public function execute(Http\Request $request) {

        $response = new Http\Response('empty index');

        return $response;
    }
}