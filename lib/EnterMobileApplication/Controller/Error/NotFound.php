<?php

namespace EnterMobileApplication\Controller\Error;

use Enter\Http;
use EnterMobileApplication\ConfigTrait;

class NotFound {
    use ConfigTrait;

    /**
     * @param Http\Request $request
     * @param string $message
     * @return Http\Response|Http\JsonResponse
     */
    public function execute(Http\Request $request, $message = 'Not Found') {
        $response = new Http\JsonResponse();
        $response->statusCode = Http\Response::STATUS_NOT_FOUND;

        $response->data['error'] = [
            'code'    => 404,
            'message' => $message,
        ];

        return $response;
    }
}