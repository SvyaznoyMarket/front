<?php

namespace EnterTerminal\Controller {

    use Enter\Http;
    use EnterTerminal\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\SessionTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterTerminal\Controller\Compare\Response;

    class Compare {
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
            $compareRepository = new \EnterRepository\Compare();

            // ид региона
            $regionId = (new \EnterTerminal\Repository\Region())->getIdByHttpRequest($request);
            if (!$regionId) {
                throw new \Exception('Не передан параметр regionId');
            }

            // сравнение из сессии
            $compare = $compareRepository->getObjectByHttpSession($session);

            $productsById = [];
            foreach ($compare->product as $compareProduct) {
                $productsById[$compareProduct->id] = null;
            }

            $productListQuery = null;
            if ((bool)$productsById) {
                $productListQuery = new Query\Product\GetListByIdList(array_keys($productsById), $regionId);
                $curl->prepare($productListQuery);
            }

            $curl->execute();

            if ($productListQuery) {
                $productsById = (new \EnterRepository\Product())->getIndexedObjectListByQueryList([$productListQuery], function(&$item) {
                    // оптимизация
                    $item['media'] = [reset($item['media'])];
                });
            }

            // сравнение свойств товара
            $compareRepository->compareProductObjectList($compare, $productsById);

            // ответ
            $response = new Response();
            $response->groups = $compareRepository->getGroupListByObject($compare, $productsById);
            foreach ($compare->product as $compareProduct) {
                $product = !empty($productsById[$compareProduct->id])
                    ? $productsById[$compareProduct->id]
                    : new Model\Product([
                        'id' => $compareProduct->id,
                    ]);

                $response->products[] = $product;
            }

            // response
            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterTerminal\Controller\Compare {
    use EnterModel as Model;

    class Response {
        /** @var Model\Product[] */
        public $groups = [];
        /** @var Model\Product[] */
        public $products = [];
    }
}