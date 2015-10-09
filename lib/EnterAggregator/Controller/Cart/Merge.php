<?php

namespace EnterAggregator\Controller\Cart {

    use Enter\Http;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\SessionTrait;
    use EnterAggregator\LoggerTrait;
    use EnterMobile\ConfigTrait;
    use EnterModel as Model;
    use EnterQuery as Query;
    use EnterRepository as Repository;
    use EnterAggregator\Controller\Cart\Merge\Request;
    use EnterAggregator\Controller\Cart\Merge\Response;

    class Merge {
        use ConfigTrait, SessionTrait, LoggerTrait, CurlTrait;

        public function execute(Request $request) {
            $config = $this->getConfig();
            $session = $this->getSession();
            $curl = $this->getCurl();
            $cartRepository = new Repository\Cart();

            $cart = $cartRepository->getObjectByHttpSession($session, $config->cart->sessionKey);

            // объединение корзины
            if ($cart->product) {
                $cartMergeQuery = new Query\Cart\SetProductList($cart->product, $request->userUi);
                $curl->prepare($cartMergeQuery);

                $curl->execute();
            }

            // запрос корзины
            $cartQuery = (new Query\Cart\GetItem($request->userUi));
            $curl->prepare($cartQuery);

            $curl->execute();

            $externalCartProductsByUi = [];
            foreach ($cartQuery->getResult()['products'] as $item) {
                if (!isset($item['uid'])) continue;

                $externalCartProductsByUi[$item['uid']] = new Model\Cart\Product($item);
            }


            if ($productUis = array_keys($externalCartProductsByUi)) {
                $productListQuery = new Query\Product\GetListByUiList($productUis, $request->regionId);
                $productDescriptionListQuery = new Query\Product\GetDescriptionListByUiList($productUis);
                $curl->prepare($productListQuery);
                $curl->prepare($productDescriptionListQuery);

                $curl->execute();

                foreach ((new Repository\Product())->getIndexedObjectListByQueryList([$productListQuery], [$productDescriptionListQuery]) as $product) {
                    /** @var Model\Cart\Product|null $cartProduct */
                    $cartProduct = isset($externalCartProductsByUi[$product->ui]) ? $externalCartProductsByUi[$product->ui] : null;
                    if (!$cartProduct) continue;

                    $cartProduct->id = (string)$product->id;

                    $cartRepository->setProductForObject($cart, $cartProduct);
                }

                $cartRepository->saveObjectToHttpSession($session, $cart, $config->cart->sessionKey);
            }

            $response = new Response();

            return $response;
        }

        /**
         * @return Merge\Request
         */
        public function createRequest() {
            return new Merge\Request();
        }
    }
}

namespace EnterAggregator\Controller\Cart\Merge {
    use EnterModel as Model;

    class Request {
        /** @var string */
        public $regionId;
        /** @var string */
        public $userUi;
    }

    class Response {
    }
}