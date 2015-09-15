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
    use EnterAggregator\Controller\Cart\Update\Request;
    use EnterAggregator\Controller\Cart\Update\Response;

    class Update {
        use ConfigTrait, SessionTrait, LoggerTrait, CurlTrait;

        public function execute(Request $request) {
            $config = $this->getConfig();
            $session = $this->getSession();
            $curl = $this->getCurl();
            $cartRepository = new Repository\Cart();

            $cart = $cartRepository->getObjectByHttpSession($session, $config->cart->sessionKey);

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
         * @return Update\Request
         */
        public function createRequest() {
            return new Update\Request();
        }
    }
}

namespace EnterAggregator\Controller\Cart\Update {
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