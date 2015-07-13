<?php

namespace EnterMobileApplication\Controller {

    use Enter\Http;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\SessionTrait;
    use EnterQuery as Query;
    use EnterModel as Model;

    class Cart {
        use LoggerTrait, CurlTrait, SessionTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $curl = $this->getCurl();
            $cartRepository = new \EnterRepository\Cart();
            
            $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request); // FIXME
            $userAuthToken = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;

            if (!$regionId) {
                throw new \Exception('Не задан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
            }

            if (!$userAuthToken) {
                throw new \Exception('Не задан параметр token', Http\Response::STATUS_BAD_REQUEST);
            }

            if (0 === strpos($userAuthToken, 'anonymous-')) {
                throw new \Exception('Параметр token содержит идентификатор анонимного пользователя (данный метод предназначен для работы только с токенами аутентифицированных пользователей)', Http\Response::STATUS_BAD_REQUEST);
            }

            $userItemQuery = new Query\User\GetItemByToken($userAuthToken);
            $curl->prepare($userItemQuery);

            $regionQuery = new Query\Region\GetItemById($regionId);
            $curl->prepare($regionQuery);
            $curl->execute();

            $user = (new \EnterRepository\User())->getObjectByQuery($userItemQuery);
            $region = (new \EnterRepository\Region())->getObjectByQuery($regionQuery);

            if (!$user || !$user->ui) {
                throw new \Exception('Не удалось получить ui пользователя', Http\Response::STATUS_BAD_REQUEST);
            }

            // Получение корзины
            call_user_func(function() use(&$cart, &$user, &$region, &$curl, &$cartRepository) {
                $cartItemQuery = new Query\Cart\GetItem($user->ui);
                $curl->prepare($cartItemQuery);
                $curl->execute();

                $cart = $cartRepository->getObjectByQuery($cartItemQuery);

                $cartProductListQuery = null;
                if ($cart->product) {
                    $cartProductListQuery = new \EnterQuery\Product\GetListByUiList(array_map(function (\EnterModel\Cart\Product $product) { return $product->ui; }, $cart->product), $region->id, ['model' => false, 'related' => false]);
                    $curl->prepare($cartProductListQuery);
                }

                $cartProductDescriptionListQuery = null;
                if ($cart->product) {
                    $cartProductDescriptionListQuery = new \EnterQuery\Product\GetDescriptionListByUiList(array_map(function(\EnterModel\Cart\Product $product) { return $product->ui; }, $cart->product), ['media' => true, 'label' => true]);
                    $curl->prepare($cartProductDescriptionListQuery);
                }

                $curl->execute();

                $cartRepository->updateObjectByQuery($cart, null, $cartProductListQuery, $cartProductDescriptionListQuery);

                $cartPriceItemQuery = new \EnterQuery\Cart\Price\GetItem($cart, $region->id);
                $curl->prepare($cartPriceItemQuery);
                $curl->execute();

                // TODO: избавиться от данного (повторного) вызова когда в v2/cart/get-price будет добавлена поддержка передачи ui товаров
                $cartRepository->updateObjectByQuery($cart, $cartPriceItemQuery);
            });

            $cacheId = $this->getCacheId($cart);

            // Данные заголовки добавляются при вызове session_start (хотя после перехода на серверную корзину данный
            // метод больше не инициализирует PHP сессии, всё равно оставляем удаление данных заголовков на случай,
            // если в будущем в данном методе появится инициализация PHP сессии)
            header_remove('Cache-Control');
            header_remove('Expires');
            header_remove('Pragma');

            $eTags = $request->getHeader('if-none-match');
            if ($eTags) {
                $eTags = array_map(function($eTag) { return trim($eTag); }, explode(',', $eTags));
                // См. RFC 2616, раздел 14.26 If-None-Match
                if (in_array($cacheId, $eTags) || in_array('*', $eTags)) {
                    $httpResponse = new Http\JsonResponse([], Http\JsonResponse::STATUS_NOT_MODIFIED);
                    $httpResponse->headers['ETag'] = $cacheId;
                    return $httpResponse;
                }
            }

            $httpResponse = new Http\JsonResponse((new \EnterMobileApplication\Repository\Cart())->getResponseArray($cart, true));
            $httpResponse->headers['ETag'] = $cacheId;

            return $httpResponse;
        }

        private function getCacheId(Model\Cart $cart) {
            $cacheId = '';
            foreach ($cart->product as $product) {
                $cacheId .= $product->id . '-' . $product->quantity . '-' . $product->sum . '|';
            }

            // См. RFC 2616, раздел 14.19 ETag
            return '"' . substr($cacheId, 0, -1) . '"';
        }
    }
}