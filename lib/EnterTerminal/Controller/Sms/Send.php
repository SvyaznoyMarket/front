<?php

namespace EnterTerminal\Controller\Sms;

use Enter\Http;
use EnterAggregator\CurlTrait;
use EnterQuery as Query;

class Send {
    use CurlTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $curl = $this->getCurl();

        if (!is_string($request->query['phone'])) {
            throw new \Exception('Параметр phone должен быть строкой', Http\Response::STATUS_BAD_REQUEST);
        }

        if (!is_string($request->query['message'])) {
            throw new \Exception('Параметр message должен быть строкой', Http\Response::STATUS_BAD_REQUEST);
        }

        $query = new Query\Sms\Send(
            $request->query['phone'],
            $request->query['message']
        );
        $curl->prepare($query);
        $curl->execute();

        // ответ
        return new Http\JsonResponse($query->getResult());
    }
}
