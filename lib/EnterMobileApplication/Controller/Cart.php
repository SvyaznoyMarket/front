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

            // корзина из сессии
            $cart = $cartRepository->getObjectByHttpSession($session);

            $productsById = [];
            foreach ($cart->product as $cartProduct) {
                $productsById[$cartProduct->id] = null;
            }

            $productListQuery = null;
            $descriptionListQuery = null;
            if ((bool)$productsById) {
                $productListQuery = new Query\Product\GetListByIdList(array_keys($productsById), $region->id);
                $curl->prepare($productListQuery);

                $descriptionListQuery = new Query\Product\GetDescriptionListByIdList(
                    array_keys($productsById),
                    [
                        'category'    => true,
                        'label'       => true,
                        'brand'       => true,
                    ]
                );
                $curl->prepare($descriptionListQuery);
            }

            $cartItemQuery = new Query\Cart\GetItem($cart, $region->id);
            $curl->prepare($cartItemQuery);

            $curl->execute();

            if ($productListQuery && $descriptionListQuery) {
                $productsById = (new \EnterRepository\Product())->getIndexedObjectListByQueryList([$productListQuery]);
                (new \EnterRepository\Product())->setDescriptionForListByListQuery($productsById, $descriptionListQuery);
            }

            // корзина из ядра
            $cartRepository->updateObjectByQuery($cart, $cartItemQuery);

            // ответ
            $response = new Response();

            $response->sum = $cart->sum;

            foreach (array_reverse($cart->product) as $cartProduct) {
                $product = !empty($productsById[$cartProduct->id])
                    ? $productsById[$cartProduct->id]
                    : new Model\Product([
                        'id' => $cartProduct->id,
                    ]);

                $product->quantity = $cartProduct->quantity; // FIXME
                $product->sum = $cartProduct->sum; // FIXME

                $response->products[] = $product;
            }

            // response
            return new Http\JsonResponse($response);
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
    }
}