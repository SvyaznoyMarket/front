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

            if (is_string($request->query['ip'])) {
                $infoQuery = new Query\Terminal\GetInfoByIp($request->query['ip']);
            } else if (is_string($request->query['ui'])) {
                $infoQuery = new Query\Terminal\GetInfoByUi($request->query['ui']);
            } else {
                throw new \Exception('Необходимо задать параметр ip или ui');
            }
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

            if ($shopId != null) {
                $shopQuery = new Query\Shop\GetItemById($shopId);
                $curl->prepare($shopQuery);
            }

            $businessRulesQuery = new Query\BusinessRule\GetList();
            $curl->prepare($businessRulesQuery);

            $curl->execute();

            if ($shopId != null) {
                $shop = (new \EnterTerminal\Repository\Shop())->getObjectByQuery($shopQuery);
                if (!$shop) {
                    throw new \Exception(sprintf('Магазин #%s не найден', $shopId));
                }

                $response->shop = $shop;
            }

            try {
                $businessRules = $businessRulesQuery->getResult();
            } catch (\Exception $e) {
                $businessRules = [];
            }

            if (!is_array($businessRules)) {
                throw new \Exception('Не удалось получить бизнес правила');
            }

            $response->businessRules = $this->filterBusinessRules($businessRules, $response->info['client_id']);

            return new Http\JsonResponse($response);
        }

        /**
         * @param $businessRules
         * @param $clientId
         * @return array
         */
        private function filterBusinessRules($businessRules, $clientId) {
            foreach ($businessRules as $key => $businessRule) {
                if (isset($businessRule['filter'])) {
                    if (isset($businessRule['filter']['api_clients']) && is_array($businessRule['filter']['api_clients'])) {
                        foreach ($businessRule['filter']['api_clients'] as $filter) {
                            if (isset($filter['alias']) && !preg_match('/^(?:' . $filter['alias'] . ')$/i', $clientId)) {
                                unset($businessRules[$key]);
                            }

                            if (isset($filter['alias_type']) && $filter['alias_type'] !== 'terminal') {
                                unset($businessRules[$key]);
                            }
                        }
                    }

                    unset($businessRules[$key]['filter']);
                }
            }

            return array_values($businessRules);
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