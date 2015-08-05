<?php

namespace EnterAggregator\Controller\Cart {

    use Enter\Http;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterMobile\ConfigTrait;
    use EnterModel as Model;
    use EnterQuery as Query;
    use EnterRepository as Repository;
    use EnterAggregator\Controller\Cart\Merge\Request;
    use EnterAggregator\Controller\Cart\Merge\Response;

    class Merge {
        use ConfigTrait, LoggerTrait, CurlTrait;

        public function execute(Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();
            $productRepository = new \EnterRepository\Product();

            // объединение корзины
            if ($request->cart->product) {
                $cartMergeQuery = new Query\Cart\SetProductList($request->cart->product, $request->userUi);
                $curl->prepare($cartMergeQuery);
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