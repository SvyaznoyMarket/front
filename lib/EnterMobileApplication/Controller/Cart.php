<?php

namespace EnterMobileApplication\Controller {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\SessionTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\Cart\Response;

    class Cart {
        use ConfigTrait, LoggerTrait, CurlTrait, SessionTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $session = $this->getSession();
            $curl = $this->getCurl();
            $cartRepository = new \EnterRepository\Cart();

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
                        'label'       => true,
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

            // ответ
            $response = new Response();

            $response->sum = $cart->sum;

            foreach (array_reverse($cart->product) as $cartProduct) {
                /** @var Model\Cart\Product $cartProduct */

                $product = new \EnterMobileApplication\Model\Cart\Product();

                $product->id = $cartProduct->id;
                $product->quantity = $cartProduct->quantity;
                $product->sum = $cartProduct->sum;

                if (!empty($productsById[$cartProduct->id])) {
                    $product->webName = $productsById[$cartProduct->id]->webName;
                    $product->namePrefix = $productsById[$cartProduct->id]->namePrefix;
                    $product->name = $productsById[$cartProduct->id]->name;
                    $product->price = $productsById[$cartProduct->id]->price;
                    $product->media = $productsById[$cartProduct->id]->media;
                }

                $response->products[] = $product;
                $response->quantity += $cartProduct->quantity;
                $response->uniqueQuantity++;
            }

            // response
            $httpResponse = new Http\JsonResponse($response);
            $httpResponse->headers['ETag'] = '"' . $cart->cacheId . '"';

            return $httpResponse;
        }
    }
}

namespace EnterMobileApplication\Controller\Cart {
    use EnterModel as Model;

    class Response {
        /** @var float */
        public $sum;
        /** @var Model\Product[] */
        public $products = [];
        /** @var int */
        public $quantity = 0;
        /** @var int */
        public $uniqueQuantity = 0;
    }
}