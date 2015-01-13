<?php

namespace Enter1C\Controller\Error;

use Enter\Http;
use Enter1C\Http\XmlResponse;
use Enter1C\ConfigTrait;

class NotFound {
    use ConfigTrait;

    /**
     * @param Http\Request $request
     * @return XmlResponse
     */
    public function execute(Http\Request $request) {
        $response = new XmlResponse();
        $response->statusCode = Http\Response::STATUS_NOT_FOUND;

        $response->data['error'] = [
            'code'    => 404,
            'message' => 'Not Found',
        ];

        return $response;
    }
}