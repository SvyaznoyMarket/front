<?php

namespace EnterMobileApplication\Controller\Error;

use Enter\Http;
use EnterMobileApplication\ConfigTrait;

class NotFoundInRegion {
    use ConfigTrait;

    /**
     * @param Http\Request $request
     * @param string $message
     * @return Http\Response|Http\JsonResponse
     */
    public function execute(Http\Request $request, $message = 'Недоступно в этом регионе') {
        $response = new Http\JsonResponse();
        $response->statusCode = Http\Response::STATUS_NOT_FOUND;

        $response->data['error'] = [
            'code'    => 100404,
            'message' => $message,
        ];

        return $response;
    }
}