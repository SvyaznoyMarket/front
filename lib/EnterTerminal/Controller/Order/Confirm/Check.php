<?php

namespace EnterTerminal\Controller\Order\Confirm;

use Enter\Http;
use EnterCurlQuery;
use EnterSite\ViewHelperTrait;
use EnterTerminal\ConfigTrait;
use EnterSite\CurlClientTrait;
use EnterSite\Controller;
use EnterTerminal\Repository;
use EnterCurlQuery as Query;

class Check {
    use ConfigTrait, CurlClientTrait {
        ConfigTrait::getConfig insteadof CurlClientTrait;
    }

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $curl = $this->getCurlClient();

        if (!is_scalar($request->query['phone'])) {
            throw new \Exception('Параметр phone должен быть строкой');
        }

        if (!is_scalar($request->query['code'])) {
            throw new \Exception('Параметр code должен быть строкой');
        }

        $contentItemQuery = new Query\Order\Confirm\Check($request->query['phone'], $request->query['code']);
        $curl->prepare($contentItemQuery);
        $curl->execute();

        return new Http\JsonResponse($contentItemQuery->getResult());
    }
}