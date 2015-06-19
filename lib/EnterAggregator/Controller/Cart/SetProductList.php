<?php

namespace EnterAggregator\Controller\Cart {

    use Enter\Http;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterMobile\ConfigTrait;
    use EnterModel as Model;
    use EnterQuery as Query;
    use EnterRepository as Repository;
    use EnterAggregator\Controller\Cart\SetProductList\Response;

    class SetProductList {
        use ConfigTrait, LoggerTrait, CurlTrait;

        /**
         * @param $regionId
         * @param Http\Session $session
         * @param Model\Cart $cart
         * @param Model\Cart\Product[] $cartProducts
         * @return Response
         */
        public function execute(
            $regionId,
            Http\Session $session,
            Model\Cart $cart,
            array $cartProducts
        ) {
            $config = $this->getConfig();
            $curl = $this->getCurl();
            $cartRepository = new Repository\Cart();
            $productRepository = new \EnterRepository\Product();

            $response = new Response();

            /** @var Model\Cart\Product[] $cartProductsById */
            $cartProductsById = [];
            foreach ($cartProducts as $cartProduct) {
                $cartProductsById[$cartProduct->id] = $cartProduct;
            }
            unset($cartProducts);

            /** @var Model\Product[] $productsById */
            $productsById = [];
            foreach ($cartProductsById as $cartProduct) {
                $productsById[$cartProduct->id] = null;
            }
            foreach ($cart->product as $cartProduct) {
                $productsById[$cartProduct->id] = null;
            }

            // запрос списка товаров
            $productListQueries = [];
            $descriptionListQueries = [];
            foreach (array_chunk(array_keys($productsById), $config->curl->queryChunkSize) as $idsInChunk) {
                $productListQuery = new Query\Product\GetListByIdList($idsInChunk, $regionId);
                $curl->prepare($productListQuery);
                $productListQueries[] = $productListQuery;

                $descriptionListQuery = new Query\Product\GetDescriptionListByIdList(
                    $idsInChunk,
                    [
                        'category' => true,
                        'label'    => true,
                        'brand'    => true,
                    ]
                );
                $curl->prepare($descriptionListQuery);
                $descriptionListQueries[] = $descriptionListQuery;
            }

            $curl->execute();

            // товары
            $productsById = $productRepository->getIndexedObjectListByQueryList($productListQueries);
            $productRepository->setDescriptionForIdIndexedListByQueryList($productsById, $descriptionListQueries);

            foreach ($cartProductsById as $cartProduct) {
                /** @var Model\Cart\Product|null $cartProduct */
                $existsCartProduct = $cartRepository->getProductById($cartProduct->id, $cart);
                if ($existsCartProduct) {
                    foreach (get_object_vars($cartProduct) as $key => $value) {
                        $existsCartProduct->{$key} = $value;
                    }
                    $cartProduct = $existsCartProduct;
                }

                /** @var Model\Product|null $product */
                $product = isset($productsById[$cartProduct->id]) ? $productsById[$cartProduct->id] : null;
                if (!$product) {
                    $this->getLogger()->push(['type' => 'error', 'error' => sprintf('Товар #%s не найден', $cartProduct->id), 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller']]);

                    continue;
                }

                $cartProduct->ui = $product->ui;

                $cartRepository->setProductForObject($cart, $cartProduct);
            }

            // сохранение корзины в сессию
            $cartRepository->saveObjectToHttpSession($session, $cart);

            // запрос корзины
            $cartItemQuery = new Query\Cart\GetItem($cart, $regionId);
            $curl->prepare($cartItemQuery);

            $curl->execute();

            try {
                // корзина из ядра
                $cartRepository->updateObjectByQuery($cart, $cartItemQuery);
            } catch (\Exception $e) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller']]);
            }

            $response->cart = $cart;
            $response->productsById = $productsById;

            return $response;
        }
    }

}

namespace EnterAggregator\Controller\Cart\SetProductList {
    use EnterModel as Model;

    class Response {
        /** @var Model\Cart */
        public $cart;
        /** @var Model\Product[] */
        public $productsById = [];
    }
}