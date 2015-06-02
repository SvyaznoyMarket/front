<?php

namespace EnterTerminal\Controller\Subscribe;

use Enter\Http;
use EnterAggregator\CurlTrait;
use EnterQuery as Query;
use EnterModel as Model;

class Create {
    use CurlTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $curl = $this->getCurl();

        if (!$email = $request->data['email']) {
            throw new \Exception('Не передан email', Http\Response::STATUS_BAD_REQUEST);
        }

        if (!$channelId = $request->data['channelId']) {
            throw new \Exception('Не передан channelId', Http\Response::STATUS_BAD_REQUEST);
        }

        $subscribe = new Model\Subscribe();
        $subscribe->email = $email;
        $subscribe->channelId = $channelId;

        $query = new Query\Subscribe\CreateItem(
            $subscribe
        );
        $curl->query($query);

        $responseData = [];
        try {
            $query->getResult();
        } catch (\Exception $e) {
            switch ($e->getCode()) {
                case 910:
                    $responseData['error'] = ['code' => $e->getCode(), 'message' => 'Подписка уже оформлена'];
                    break;
                default:
                    $responseData['error'] = ['code' => $e->getCode(), 'message' => $e->getMessage()];
            }
        }

        // ответ
        return new Http\JsonResponse($responseData, empty($responseData['error']) ? Http\Response::STATUS_OK : Http\Response::STATUS_INTERNAL_SERVER_ERROR);
    }
}
