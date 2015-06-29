<?php

namespace EnterMobileApplication\Controller\Cart;

use Enter\Http;
use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterMobileApplication\ConfigTrait;
use EnterAggregator\SessionTrait;
use EnterMobileApplication\Controller;
use EnterQuery as Query;

class IncreaseQuantityForProductList {
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

        $ids = (array)$request->data['ids'];
        $uis = (array)$request->data['uis'];
        $quantity = isset($request->data['quantity']) ? (int)$request->data['quantity'] : 1;

        if (!$ids && !$uis) {
            throw new \Exception('Не переданы параметры ids и uis', Http\Response::STATUS_BAD_REQUEST);
        }

        $cart = $cartRepository->getObjectByHttpSession($session, $config->cart->sessionKey);

        foreach ($ids as $id) {
            if (isset($cart->product[$id])) {
                $cart->product[$id]->quantity += $quantity;
            }
        }

        if ($uis) {
            $uisToIds = [];
            foreach ($cart->product as $product) {
                $uisToIds[$product->ui] = $product->id;
            }

            foreach ($uis as $ui) {
                if (isset($uisToIds[$ui]) && isset($cart->product[$uisToIds[$ui]])) {
                    $cart->product[$uisToIds[$ui]]->quantity += $quantity;
                }
            }
        }

        $cart->cacheId++;

        $cartRepository->saveObjectToHttpSession($session, $cart, $config->cart->sessionKey);
        
        return new Http\JsonResponse(['cart' => (new \EnterMobileApplication\Repository\Cart())->getResponseArray($cart)]);
    }
}
