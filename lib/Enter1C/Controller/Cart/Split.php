<?php

namespace Enter1C\Controller\Cart {

    use Enter\Http;
    use Enter1C\Http\XmlResponse;
    use Enter1C\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use Enter1C\Repository;
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

            // ответ
            $response = new Response();

            $requestData = json_decode(json_encode(simplexml_load_string($request->getContent())), true);

            $cart = new Model\Cart();
            foreach ($requestData['cart']['product'] as $productItem) {
                $cart->product[] = new Model\Cart\Product($productItem);
            }

            // удаление ненужных данных
            //foreach ($requestData[])

            $splitQuery = new Query\Cart\Split\GetItem(
                $cart,
                $requestData['geo_ui'],
                null,
                null,
                isset($requestData['previous_split']) ? $requestData['previous_split'] : [],
                isset($requestData['changes']) ? $requestData['changes'] : []
            );
            $splitQuery->setTimeout($config->coreService->timeout * 2);
            $curl->prepare($splitQuery);

            $curl->execute();

            // данные разбиения
            $splitData = $splitQuery->getResult();

            // разбиение
            $split = new Model\Cart\Split($splitData);

            $response->split = (new Repository\Cart\Split())->dumpObject($split);

            // response
            return new XmlResponse($response);
        }
    }
}

namespace Enter1C\Controller\Cart\Split {
    use EnterModel as Model;

    class Response {
        /** @var array */
        public $split;
    }
}