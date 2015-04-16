<?php

namespace EnterTerminal\Controller {

    use Enter\Http;
    use EnterTerminal\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\SessionTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterTerminal\Controller\Cart\Response;

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
            $productRepository = new \EnterRepository\Product();

            // ид региона
            $regionId = (new \EnterTerminal\Repository\Region())->getIdByHttpRequest($request);
            if (!$regionId) {
                throw new \Exception('Не передан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
            }

            // корзина из сессии
            $cart = $cartRepository->getObjectByHttpSession($session);

            $productsById = [];
            foreach ($cart->product as $cartProduct) {
                $productsById[$cartProduct->id] = null;
            }

            $descriptionListQuery = null;
            $productListQuery = null;
            if ((bool)$productsById) {
                $productListQuery = new Query\Product\GetListByIdList(array_keys($productsById), $regionId);
                $curl->prepare($productListQuery);

                $descriptionListQuery = new Query\Product\GetDescriptionListByIdList(
                    array_keys($productsById),
                    [
                        'media'       => true,
                        'media_types' => ['main'], // только главная картинка
                    ]
                );
                $curl->prepare($descriptionListQuery);
            }

            $cartItemQuery = new Query\Cart\GetItem($cart, $regionId);
            $curl->prepare($cartItemQuery);

            $curl->execute();

            if ($productListQuery) {
                $productsById = $productRepository->getIndexedObjectListByQueryList([$productListQuery]);

                // товары по ui
                $productsByUi = [];
                call_user_func(function() use (&$productsById, &$productsByUi) {
                    foreach ($productsById as $product) {
                        $productsByUi[$product->ui] = $product;
                    }
                });

                // медиа для товаров
                $productRepository->setDescriptionForListByListQuery($productsByUi, $descriptionListQuery);
            }

            // корзина из ядра
            $cartRepository->updateObjectByQuery($cart, $cartItemQuery);

            // ответ
            $response = new Response();

            $response->sum = $cart->sum;
            $response->quantity = count($cart);

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

namespace EnterTerminal\Controller\Cart {
    use EnterModel as Model;

    class Response {
        /** @var float */
        public $sum;
        /** @var int */
        public $quantity;
        /** @var Model\Product[] */
        public $products = [];
    }
}