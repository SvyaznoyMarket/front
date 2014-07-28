<?php

namespace EnterMobileApplication\Controller\Error;

use Enter\Http;
use EnterMobileApplication\ConfigTrait;

class NotFound {
    use ConfigTrait;

    /**
     * @param Http\Request $request
     * @return Http\Response|Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $response = new Http\JsonResponse();
        $response->statusCode = Http\JsonResponse::STATUS_NOT_FOUND;

        $response->data['error'] = [
            'code'    => 404,
            'message' => 'Not Found',
        ];

        return $response;
    }
}