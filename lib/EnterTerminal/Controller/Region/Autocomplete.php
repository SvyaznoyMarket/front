<?php

namespace EnterTerminal\Controller\Region {

    use Enter\Http;
    use EnterTerminal\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterTerminal\Controller\Region\Autocomplete\Response;

    class Autocomplete {
        use ConfigTrait, CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $curl = $this->getCurl();

            // ответ
            $response = new Response();

            $keyword = trim((string)$request->query['q']);
            $limit = (int)$request->query['limit'] ?: 10;
            if ($limit > 100) {
                $limit = 100;
            }

            $regionListQuery = new Query\Region\GetListByKeyword($keyword);
            $curl->prepare($regionListQuery)->execute();

            $i = 0;
            foreach ($regionListQuery->getResult() as $regionItem) {
                if ($i >= $limit) break;

                $region = new Model\Region($regionItem);
                if (isset($regionItem['region']['id'])) {
                    $region->parent = new Model\Region($regionItem['region']);
                }

                $response->regions[] = $region;

                $i++;
            }

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterTerminal\Controller\Region\Autocomplete {
    use EnterModel as Model;

    class Response {
        /** @var Model\Region[] */
        public $regions = [];
    }
}