<?php

namespace EnterMobile\Controller\User\Subscribe;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterAggregator\DebugContainerTrait;
use EnterMobile\Controller\SecurityTrait;
use EnterMobile\Repository;
use EnterQuery as Query;

class Set {
    use
        SecurityTrait,
        ConfigTrait,
        LoggerTrait,
        CurlTrait,
        MustacheRendererTrait,
        DebugContainerTrait,
        SessionTrait;

    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();

        $subscribeItem = is_array($request->data['subscribe']) ? $request->data['subscribe'] : [];
        $subscribeItem += [
            'is_confirmed' => true,
        ];
        $subscribe = new \EnterModel\Subscribe($subscribeItem);

        $userToken = $this->getUserToken($request);

        $setQuery = new Query\Subscribe\SetItemByUserToken($userToken, $subscribe);
        $curl->prepare($setQuery);

        $curl->execute();

        $error = $setQuery->getError();

        $responseData = [
            'success' => (bool)$error,
            'error'   =>
                $error
                ? ['code' => $error->getCode(), 'message' => $error->getMessage()]
                : null
            ,
        ];

        // response
        $response = new Http\JsonResponse([
            'result' => $responseData,
        ]);

        return $response;
    }
}