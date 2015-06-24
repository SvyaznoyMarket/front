<?php

namespace EnterMobileApplication\Controller {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\SessionTrait;
    use EnterQuery as Query;
    use EnterModel as Model;

    class Cart {
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
            
            $userAuthToken = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;
            $user = null;
            if ($userAuthToken && (0 !== strpos($userAuthToken, 'anonymous-'))) {
                try {
                    $userItemQuery = new Query\User\GetItemByToken($userAuthToken);
                    $curl->prepare($userItemQuery)->execute();
                    $user = (new \EnterRepository\User())->getObjectByQuery($userItemQuery);
                } catch (\Exception $e) {
                    $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller']]);
                }
            }
            
            // MAPI-56
            $session = $this->getSession($user && $user->ui ? $user->ui : null);

            // Данные заголовки добавляются при вызове session_start
            header_remove('Cache-Control');
            header_remove('Expires');
            header_remove('Pragma');

            // корзина из сессии
            $cart = $cartRepository->getObjectByHttpSession($session, $config->cart->sessionKey);

            $eTags = $request->getHeader('if-none-match');
            if ($eTags) {
                $eTags = array_map(function($etag) { return trim($etag); }, explode(',', $eTags));
                // См. RFC 2616, раздел 14.26 If-None-Match
                if (in_array('"' . $cart->cacheId . '"', $eTags) || in_array('*', $eTags)) {
                    $httpResponse = new Http\JsonResponse([], Http\JsonResponse::STATUS_NOT_MODIFIED);
                    $httpResponse->headers['ETag'] = '"' . $cart->cacheId . '"';
                    return $httpResponse;
                }
            }

            // ид региона
            $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request); // FIXME
            if (!$regionId) {
                throw new \Exception('Не указан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
            }

            // запрос региона
            $regionQuery = new Query\Region\GetItemById($regionId);
            $curl->prepare($regionQuery);

            $curl->execute();

            // регион
            $region = (new \EnterRepository\Region())->getObjectByQuery($regionQuery);

            $productsById = [];
            foreach ($cart->product as $cartProduct) {
                $productsById[$cartProduct->id] = null;
            }

            $productListQuery = null;
            $descriptionListQuery = null;
            if ($productsById) {
                $productListQuery = new Query\Product\GetListByIdList(array_keys($productsById), $region->id);
                $curl->prepare($productListQuery);

                $descriptionListQuery = new Query\Product\GetDescriptionListByIdList(
                    array_keys($productsById),
                    [
                        'media'       => true,
                    ]
                );
                $curl->prepare($descriptionListQuery);
            }

            $cartItemQuery = new Query\Cart\GetItem($cart, $region->id);
            $curl->prepare($cartItemQuery);

            $curl->execute();

            if ($productListQuery) {
                $productsById = (new \EnterRepository\Product())->getIndexedObjectListByQueryList([$productListQuery]);
            }

            if ($descriptionListQuery) {
                (new \EnterRepository\Product())->setDescriptionForIdIndexedListByQueryList($productsById, [$descriptionListQuery]);
            }

            // корзина из ядра
            $cartRepository->updateObjectByQuery($cart, $cartItemQuery);

            // response
            $httpResponse = new Http\JsonResponse((new \EnterMobileApplication\Repository\Cart())->getResponseArray($cart, $productsById));
            $httpResponse->headers['ETag'] = '"' . $cart->cacheId . '"';

            return $httpResponse;
        }
    }
}