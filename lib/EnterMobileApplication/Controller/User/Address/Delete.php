<?php

namespace EnterMobileApplication\Controller\User\Address;

use Enter\Http;
use EnterMobileApplication\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\DebugContainerTrait;
use EnterQuery as Query;
use EnterMobileApplication\Controller;
use EnterMobileApplication\Repository;

class Delete {
    use ConfigTrait, LoggerTrait, CurlTrait, SessionTrait, DebugContainerTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();
        //$session = $this->getSession();

        $token = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;
        if (!$token) {
            throw new \Exception('Не указан token', Http\Response::STATUS_BAD_REQUEST);
        }
        $id = is_scalar($request->query['id']) ? (string)$request->query['id'] : null;
        if (!$id) {
            throw new \Exception('Не указан id', Http\Response::STATUS_BAD_REQUEST);
        }

        try {
            $userItemQuery = new Query\User\GetItemByToken($token);
            $curl->prepare($userItemQuery);

            $curl->execute();

            $user = (new \EnterRepository\User())->getObjectByQuery($userItemQuery);
            /*
            if ($user) {
                $response->token = $token;
            }
            */

            $addressItemQuery = new Query\User\Address\DeleteItemByUserUi($user->ui, $id);
            $addressItemQuery->setTimeout(3 * $config->crmService->timeout);
            $curl->prepare($addressItemQuery);

            $curl->execute();

            $addressItemQuery->getResult();
        } catch (\Exception $e) {
            if ($config->debugLevel) $this->getDebugContainer()->error = $e;
        }

        return new Http\JsonResponse([]);
    }
}
