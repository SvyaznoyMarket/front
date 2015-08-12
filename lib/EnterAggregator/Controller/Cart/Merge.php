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
            if ($request->cart->product) {
                $cartMergeQuery = new Query\Cart\SetProductList($request->cart->product, $request->userUi);
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
                $curl->prepare($productListQuery);

                $curl->execute();

                foreach ($productListQuery->getResult() as $item) {
                    /** @var Model\Cart\Product|null $cartProduct */
                    $cartProduct = (isset($item['ui']) && isset($externalCartProductsByUi[$item['ui']])) ? $externalCartProductsByUi[$item['ui']] : null;
                    if (!$cartProduct) continue;

                    $cartProduct->id = (string)$item['id'];

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
        /** @var Model\Cart */
        public $cart;
    }

    class Response {
    }
}