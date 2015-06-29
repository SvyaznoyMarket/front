<?php

namespace EnterMobileApplication\Controller\Cart;

use Enter\Http;
use EnterAggregator\LoggerTrait;
use EnterMobileApplication\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\SessionTrait;
use EnterMobileApplication\Controller;
use EnterQuery as Query;

class SetProductList {
    use ConfigTrait, LoggerTrait, CurlTrait, SessionTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();
        $cartRepository = new \EnterRepository\Cart();
        
        $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request);
        if (!$regionId) {
            throw new \Exception('Не указан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
        }
        
        $regionQuery = new Query\Region\GetItemById($regionId);
        $curl->prepare($regionQuery);
        
        $userAuthToken = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;
        $userItemQuery = null;
        if ($userAuthToken && (0 !== strpos($userAuthToken, 'anonymous-'))) {
            $userItemQuery = new Query\User\GetItemByToken($userAuthToken);
            $curl->prepare($userItemQuery);
        }
        
        $curl->execute();
        
        $region = (new \EnterRepository\Region())->getObjectByQuery($regionQuery);
        
        $user = null;
        if ($userItemQuery) {
            try {
                $user = (new \EnterRepository\User())->getObjectByQuery($userItemQuery);
            } catch (\Exception $e) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller']]);
            }
        }
        
        // MAPI-56
        $session = $this->getSession($user && $user->ui ? $user->ui : null);

        // товара для корзины
        $cartProducts = $cartRepository->getProductObjectListByHttpRequest($request);
        if (!$cartProducts) {
            throw new \Exception('Товары не получены', Http\Response::STATUS_BAD_REQUEST);
        }

        // корзина из сессии
        $cart = $cartRepository->getObjectByHttpSession($session, $config->cart->sessionKey);

        $productsById = [];
        foreach ($cartProducts as $cartProduct) {
            $productsById[$cartProduct->id] = null;
        }

        if ($productsById) {
            $productListQuery = new Query\Product\GetListByIdList(array_keys($productsById), $region->id);
            $curl->prepare($productListQuery);
            $curl->execute();

            $productsById = (new \EnterRepository\Product())->getIndexedObjectListByQueryList([$productListQuery]);
        }

        // добавление товара в корзину
        foreach ($cartProducts as $cartProduct) {
            $cartProduct->ui = $productsById[$cartProduct->id]->ui;
			
			// MAPI-57
			if ($cartProduct->quantity <= 0) {
				if (isset($cart->product[$cartProduct->id])) {
					$cartProduct->quantity = $cart->product[$cartProduct->id]->quantity;
				}
				
				$cartProduct->quantity++;
			}
			
			// MAPI-57
			if (!$cartProduct->sender && isset($cart->product[$cartProduct->id])) {
				$cartProduct->sender = $cart->product[$cartProduct->id]->sender;
			}
			
			$cart->product[$cartProduct->id] = $cartProduct;
        }

        $cart->cacheId++;

        $cartRepository->saveObjectToHttpSession($session, $cart, $config->cart->sessionKey);

        return new Http\JsonResponse(['cart' => (new \EnterMobileApplication\Repository\Cart())->getResponseArray($cart)]);
    }
}
