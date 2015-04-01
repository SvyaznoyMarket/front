<?php

namespace EnterMobileApplication\Controller\Search {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\Search\Autocomplete\Response;

    class Autocomplete {
        use ConfigTrait, CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $curl = $this->getCurl();
            $config = $this->getConfig();

            $searchRepository = new \EnterRepository\Search();

            // ид региона
            $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request);
            if (!$regionId) {
                throw new \Exception('Не передан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
            }

            $searchPhrase = $request->query['phrase'] ?: null;
            if (!$searchPhrase) {
                throw new \Exception('Не передан параметр phrase', Http\Response::STATUS_BAD_REQUEST);
            }

            // запрос autocomplete
            $query = new Query\Search\GetAutocompleteResultByPhrase($searchPhrase, $regionId);
            $query->setTimeout($config->coreService->timeout / 2);
            $curl->prepare($query);

            $curl->execute();

            // ответ
            $response = new Response();
            $autocompleteResult = $searchRepository->getAutocompleteObjectByQuery($query);
            if (null !== $autocompleteResult) {
                $response->categories = $autocompleteResult->categories;
                $response->products = $autocompleteResult->products;
            }

            // response
            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterMobileApplication\Controller\Search\Autocomplete {
    use EnterModel as Model;

    class Response {
        /** @var array */
        public $categories = [];
        /** @var array */
        public $products = [];
    }
}