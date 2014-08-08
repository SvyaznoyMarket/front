<?php

namespace Enter1C\Controller\Cart {

    use Enter\Http;
    use Enter1C\Http\XmlResponse;
    use Enter1C\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use Enter1C\Controller;
    use Enter1C\Controller\Cart\Split\Response;

    class Split {
        use ConfigTrait, LoggerTrait, CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return XmlResponse
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();

            $requestData = json_decode(json_encode(simplexml_load_string($request->getContent())), true);

            $cart = new Model\Cart();
            foreach ($requestData['cart']['product'] as $productItem) {
                $cart->product[] = new Model\Cart\Product($productItem);
            }

            $splitQuery = new Query\Cart\Split\GetItem(
                $cart,
                $requestData['geo_ui']
            );
            $splitQuery->setTimeout($config->coreService->timeout * 2);
            $curl->prepare($splitQuery);

            $curl->execute();

            // разбиение
            $splitData = $splitQuery->getResult();

            // ответ
            $response = new Response();

            $response->cart = $cart;
            $response->split = $splitData;

            // response
            return new XmlResponse($response);
        }
    }
}

namespace Enter1C\Controller\Cart\Split {
    use EnterModel as Model;

    class Response {
        /** @var Model\Cart */
        public $cart;
        /** @var array */
        public $split;
    }
}