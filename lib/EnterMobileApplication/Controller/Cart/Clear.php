<?php

namespace EnterMobileApplication\Controller\Cart;

use Enter\Http;
use EnterMobileApplication\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\SessionTrait;
use EnterQuery as Query;
use EnterModel as Model;
use EnterMobileApplication\Controller;

class Clear {
    use ConfigTrait, LoggerTrait, CurlTrait, SessionTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $cartRepository = new \EnterRepository\Cart();
        
        $userAuthToken = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;
        $user = null;
        if ($userAuthToken && (0 !== strpos($userAuthToken, 'anonymous-'))) {
            try {
                $userItemQuery = new Query\User\GetItemByToken($userAuthToken);
                $this->getCurl()->prepare($userItemQuery)->execute();
                $user = (new \EnterRepository\User())->getObjectByQuery($userItemQuery);
            } catch (\Exception $e) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller']]);
            }
        }
        
        // MAPI-56
        $session = $this->getSession($user && $user->ui ? $user->ui : null);

        $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request);
        if (!$regionId) {
            throw new \Exception('Не указан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
        }

        // корзина из сессии
        $cart = $cartRepository->getObjectByHttpSession($session, $config->cart->sessionKey);

        // удаление товаров
        $cart->product = [];

        $cart->cacheId++;

        // сохранение корзины в сессию
        $cartRepository->saveObjectToHttpSession($session, $cart, $config->cart->sessionKey);

        // response
        return new Http\JsonResponse([]);
    }
}