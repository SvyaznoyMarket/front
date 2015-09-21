<?php

namespace EnterAggregator\Controller\Cart {

    use EnterMobile\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\AbTestTrait;
    use EnterAggregator\LoggerTrait;
    use EnterModel as Model;
    use EnterQuery as Query;
    use EnterRepository as Repository;
    use EnterAggregator\Controller\Cart\SetProductList\Response;

    class SetProductList {
        use ConfigTrait, CurlTrait, AbTestTrait, LoggerTrait;

        /**
         * @param SetProductList\Request $request
         * @return Response
         * @throws \Exception
         */
        public function execute(SetProductList\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();
            $cartRepository = new Repository\Cart();
            $productRepository = new \EnterRepository\Product();

            $response = new Response();

            /** @var Model\Cart\Product[] $cartProductsById */
            $cartProductsById = [];
            foreach ($request->cartProducts as $cartProduct) {
                $cartProductsById[$cartProduct->id] = $cartProduct;
            }

            /** @var Model\Product[] $productsById */
            $productsById = [];
            foreach ($cartProductsById as $cartProduct) {
                $productsById[$cartProduct->id] = null;
            }
            foreach ($request->cart->product as $cartProduct) {
                $productsById[$cartProduct->id] = null;
            }

            // запрос списка товаров
            $productListQueries = [];
            $descriptionListQueries = [];
            foreach (array_chunk(array_keys($productsById), $config->curl->queryChunkSize) as $idsInChunk) {
                $productListQuery = new Query\Product\GetListByIdList($idsInChunk, $request->regionId);
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

            /** @var Query\Cart\SetQuantityForProductItem[] $setProductQueries */
            $setProductQueries = [];

            // товары
            $productsById = $productRepository->getIndexedObjectListByQueryList($productListQueries, $descriptionListQueries);

            foreach ($cartProductsById as $cartProduct) {
                /** @var Model\Cart\Product|null $cartProduct */
                $existsCartProduct = $cartRepository->getProductById($cartProduct->id, $request->cart);
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

                $cartRepository->setProductForObject($request->cart, $cartProduct);

                if ($request->userUi && $this->getAbTest()->isCoreCartEnabled()) {
                    $setProductQuery = new Query\Cart\SetQuantityForProductItem($cartProduct->ui, $cartProduct->quantity, $request->userUi);
                    $curl->prepare($setProductQuery);
                    $setProductQueries[] = $setProductQuery;
                }
            }

            // сохранение корзины в сессию
            $cartRepository->saveObjectToHttpSession($request->session, $request->cart, $config->cart->sessionKey);

            if ($setProductQueries) {
                $curl->execute(null, 1);
            }

            // запрос корзины
            $cartItemQuery = new Query\Cart\Price\GetItem($request->cart, $request->regionId);
            $curl->prepare($cartItemQuery);

            $curl->execute();

            try {
                // корзина из ядра
                $cartRepository->updateObjectByQuery($request->cart, $cartItemQuery);
            } catch (\Exception $e) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller']]);
            }

            $response->cart = $request->cart;
            $response->productsById = $productsById;

            return $response;
        }

        /**
         * @return SetProductList\Request
         */
        public function createRequest() {
            return new SetProductList\Request();
        }
    }
}

namespace EnterAggregator\Controller\Cart\SetProductList {
    use Enter\Http;
    use EnterModel as Model;

    class Request {
        /** @var string */
        public $regionId;
        /** @var Http\Session */
        public  $session;
        /** @var Model\Cart */
        public $cart;
        /** @var Model\Cart\Product[] */
        public $cartProducts;
        /** @var string|null */
        public $userUi;
    }

    class Response {
        /** @var Model\Cart */
        public $cart;
        /** @var Model\Product[] */
        public $productsById = [];
    }
}