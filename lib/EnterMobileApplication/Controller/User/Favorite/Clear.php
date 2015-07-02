<?php

namespace EnterMobileApplication\Controller\User\Favorite;

use Enter\Http;
use EnterMobileApplication\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\DebugContainerTrait;
use EnterQuery as Query;
use EnterMobileApplication\Controller;
use EnterMobileApplication\Repository;

class Clear {
    use ConfigTrait, LoggerTrait, CurlTrait, SessionTrait, DebugContainerTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();

        $token = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;
        if (!$token) {
            throw new \Exception('Не указан token', Http\Response::STATUS_BAD_REQUEST);
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

            $favoriteItemQuery = new Query\User\Favorite\DeleteListByUserUi($user->ui);
            $favoriteItemQuery->setTimeout(3 * $config->crmService->timeout);
            $curl->query($favoriteItemQuery);

            $favoriteItemQuery->getResult();
        } catch (\Exception $e) {
            if ($config->debugLevel) $this->getDebugContainer()->error = $e;
        }

        return (new Controller\User\Favorite\Get())->execute($request);
    }
}
