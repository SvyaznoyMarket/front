<?php

namespace EnterTerminal\Controller\Search {

    use Enter\Http;
    use EnterTerminal\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\SessionTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterTerminal\Controller\Search\Autocomplete\Response;

    class Autocomplete {
        use CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $curl = $this->getCurl();

            // ид региона
            $regionId = (new \EnterTerminal\Repository\Region())->getIdByHttpRequest($request);
            if (!$regionId) {
                throw new \Exception('Не передан параметр regionId');
            }

            $searchRepository = new \EnterRepository\Search();
            $searchPhrase = $searchRepository->getPhraseByHttpRequest($request);

            // запрос autocomplete
            $query = new Query\Search\GetAutocompleteResultByPhrase($searchPhrase, $regionId);
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

namespace EnterTerminal\Controller\Search\Autocomplete {
    use EnterModel as Model;

    class Response {
        /** @var array */
        public $categories = [];
        /** @var array */
        public $products = [];
    }
}