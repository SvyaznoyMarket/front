<?php

namespace EnterTerminal\Controller {

    use Enter\Http;
    use EnterAggregator\CurlTrait;
    use EnterQuery as Query;
    use EnterTerminal\Controller\Config\Response;

    class Config {
        use CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $curl = $this->getCurl();

            if (!is_string($request->query['ip'])) {
                throw new \Exception('Параметр ip должен быть строкой');
            }

            $infoQuery = new Query\Terminal\GetInfoByIp($request->query['ip']);
            $curl->prepare($infoQuery);
            $curl->execute();

            // ответ
            $response = new Response();

            $data = $infoQuery->getResult();
            if (!$data) {
                throw new \Exception('Не удалось получить конфигурацию терминала');
            }

            $response->info = $data;

            $shopId = $response->info['shop_id'];

            $shopQuery = new Query\Shop\GetItemById($shopId);
            $curl->prepare($shopQuery);

            $businessRulesQuery = new Query\BusinessRules\GetList();
            $curl->prepare($businessRulesQuery);

            $curl->execute();

            $shop = (new \EnterTerminal\Repository\Shop())->getObjectByQuery($shopQuery);
            if (!$shop) {
                throw new \Exception(sprintf('Магазин #%s не найден', $shopId));
            }

            $response->shop = $shop;

            $businessRules = $businessRulesQuery->getResult();
            if (!$businessRules) {
                throw new \Exception('Не удалось получить бизнес правила');
            }

            foreach ($businessRules as $key => $businessRule) {
                if (isset($businessRule['filter'])) {
                    if (isset($businessRule['filter']['api_clients']['alias']) && !preg_match('/' . $businessRule['filter']['api_clients']['alias'] . '/s', $response->info['client_id'])) {
                        unset($businessRules[$key]);
                    }

                    if (isset($businessRule['filter']['api_clients']['alias_type']) && $businessRule['filter']['api_clients']['alias_type'] !== 'terminal') {
                        unset($businessRules[$key]);
                    }

                    unset($businessRules[$key]['filter']);
                }
            }

            $response->businessRules = array_values($businessRules);

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterTerminal\Controller\Config {
    use EnterModel as Model;

    class Response {
        /** @var \EnterModel\Shop */
        public $shop;
        /** @var array */
        public $info;
        /** @var array */
        public $businessRules;
    }
}